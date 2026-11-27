<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Test;

use Closure;
use Icinga\Module\Kubernetes\Common\NotificationsConfig;
use Icinga\Module\Kubernetes\Common\NotificationsDiscovery;
use Icinga\Module\Kubernetes\Common\NotificationsSource;
use Icinga\Module\Kubernetes\Common\NotificationsUrl;
use Icinga\Module\Kubernetes\Model\Config;
use Icinga\Module\Notifications\Integrations\V1\Source;
use ipl\Sql\Adapter\Mysql;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Contract\Adapter;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use RuntimeException;
use stdClass;

class NotificationsConfigTest extends TestCase
{
    /** @var string */
    protected const CLUSTER = 'd64a8827-2de6-4915-9aad-7c75fb32e798';

    /** @var string */
    protected const OTHER_CLUSTER = '471d54de-dc99-4d5a-b794-a1a17d847980';

    /** @var string */
    protected const SOCKET_PATH = '/run/icinga-notifications/api.sock';

    /** @var string */
    protected const SOCKET_URL = 'unix:///run/icinga-notifications/api.sock';

    /** @var string */
    protected const HTTP_URL = 'http://notifications.example:5680';

    /** @var array<string, string> */
    protected array $settings = [];

    /** @var array<string, bool> */
    protected array $locks = [];

    /** @var list<stdClass> */
    protected array $instances = [];

    protected int $transactions = 0;

    protected ?int $failCommit = null;

    /** @var list<array<string, mixed>> */
    protected array $deletes = [];

    /** @var list<array<string, string>> */
    protected array $inserts = [];

    /**
     * @return array<string, array{string, bool, bool}>
     */
    public static function urls(): array
    {
        return [
            'disabled'          => ['', true, false],
            'http'              => ['http://localhost:5680', true, true],
            'https'             => ['https://notifications.example/events', true, true],
            'Docker hostname'   => ['http://icinga_notifications:5680', true, true],
            'IPv6'              => ['http://[::1]:5680', true, true],
            'mapped IPv6'       => ['http://[::ffff:192.0.2.1]:5680', true, true],
            'IPv6 zone'         => ['http://[fe80::1%25en0]:5680', true, true],
            'unclosed IPv6'     => ['http://[::1', false, false],
            'IPv6 host suffix'  => ['http://[::1]suffix:5680', false, false],
            'invalid IPv6'      => ['http://[127.0.0.1]:5680', false, false],
            'IDN hostname'      => ['https://über.example/events', true, true],
            'escaped HTTP path' => ['https://notifications.example/events%2Factive%25', true, true],
            'bad HTTP escape'   => ['https://notifications.example/%zz', false, false],
            'short HTTP escape' => ['http://notifications.example/events%2', false, false],
            'escaped userinfo'  => ['https://user%40test:p%25@notifications.example', true, true],
            'bad user escape'   => ['https://user%ZZ@notifications.example', false, false],
            'bad pass escape'   => ['https://user:p%ZZ@notifications.example', false, false],
            'escaped fragment'  => ['https://notifications.example/#100%25', true, true],
            'bad fragment'      => ['https://notifications.example/#100%', false, false],
            'opaque HTTP query' => ['https://notifications.example/?value=100%', true, true],
            'raw newline'       => ["http://notifications.example/\n", false, false],
            'host whitespace'   => ['http://invalid host:5680', false, false],
            'unix'              => ['unix:///run/icinga-notifications.sock', true, false],
            'escaped path'      => ['unix:///run/socket%20%23%3F%25.sock', true, false],
            'relative path'     => ['unix:run/socket', false, false],
            'relative URL'      => ['/run/socket', false, false],
            'authority'         => ['unix://localhost/run/socket', false, false],
            'userinfo'          => ['unix://user@localhost/run/socket', false, false],
            'port'              => ['unix://localhost:5680/run/socket', false, false],
            'extra slash'       => ['unix:////run/socket', false, false],
            'empty socket path' => ['unix:///', false, false],
            'query'             => ['unix:///run/socket?test=1', false, false],
            'fragment'          => ['unix:///run/socket#test', false, false],
            'raw space'         => ['unix:///run/socket name', false, false],
            'bad escape'        => ['unix:///run/socket%zz', false, false],
            'null byte'         => ['unix:///run/socket%00', false, false],
            'raw null byte'     => ["unix:///run/socket\0", false, false],
            '255-byte Unix URL' => ['unix:///' . str_repeat('%20', 82) . 'x', true, false],
            '256-byte Unix URL' => ['unix:///' . str_repeat('%20', 82) . 'xx', false, false],
            'http missing host' => ['https:///events', false, false],
            'unknown scheme'    => ['ftp://notifications.example', false, false],
            'scheme missing'    => ['notifications.example:5680', false, false],
        ];
    }

    #[DataProvider('urls')]
    public function testSupportedUrls(string $url, bool $valid, bool $web): void
    {
        self::assertSame($valid, NotificationsUrl::isValid($url));
        self::assertSame($web, NotificationsUrl::isWeb($url));
    }

    public function testDiscoveredPathEscapesReservedCharacters(): void
    {
        self::assertSame(
            'unix:///run/socket%20%23%3F%25.sock',
            NotificationsUrl::fromSocketPath('/run/socket #?%.sock'),
        );
    }

    public function testUnrepresentableSocketUrlHasNoConfirmation(): void
    {
        $discovery = new NotificationsDiscovery('/' . str_repeat(' ', 82) . 'xx', 'kubernetes');

        self::assertNull($discovery->getConfirmation(static::CLUSTER));
    }

    public function testDiscoveryIgnoresStaleInstancesAndSupportsMissingMetadata(): void
    {
        $now = 2_000_000_000;
        $discovery = NotificationsDiscovery::fromRows([
            (object) ['heartbeat'                      => ($now - 61) * 1000, 'kubernetes_service_user' => 'old',
                'notifications_discovered_socket_path' => '/old/socket'],
            (object) ['heartbeat'                      => ($now - 60) * 1000, 'kubernetes_service_user' => 'kubernetes',
                'notifications_discovered_socket_path' => '/run/socket #?%'],
        ], $now);
        self::assertSame('/run/socket #?%', $discovery->socketPath);
        self::assertSame('kubernetes', $discovery->serviceUser);
        $missing = NotificationsDiscovery::fromRows([(object) ['heartbeat' => $now * 1000]], $now);
        self::assertNull($missing->socketPath);
        self::assertNull($missing->serviceUser);
        $stale = NotificationsDiscovery::fromRows([(object) ['heartbeat' => ($now - 61) * 1000]], $now);
        self::assertNull($stale->socketPath);
        self::assertNull($stale->serviceUser);
    }

    public function testDiscoveryRejectsConflictingLivePathsAndUsers(): void
    {
        $now = 2_000_000_000;
        $rows = [
            (object) ['heartbeat'                      => $now * 1000, 'kubernetes_service_user' => 'first',
                'notifications_discovered_socket_path' => '/run/first'],
            (object) ['heartbeat'                      => $now * 1000, 'kubernetes_service_user' => 'second',
                'notifications_discovered_socket_path' => '/run/second'],
        ];
        $discovery = NotificationsDiscovery::fromRows($rows, $now);
        self::assertNull($discovery->socketPath);
        self::assertNull($discovery->serviceUser);
        $rows[1]->kubernetes_service_user = 'first';
        self::assertSame('first', NotificationsDiscovery::fromRows($rows, $now)->serviceUser);
        $rows[1]->notifications_discovered_socket_path = null;
        self::assertNull(NotificationsDiscovery::fromRows($rows, $now)->socketPath);
        $rows[0]->kubernetes_service_user = '1001';
        $rows[1]->kubernetes_service_user = '01001';
        self::assertNull(NotificationsDiscovery::fromRows($rows, $now)->serviceUser);
    }

    public function testDiscoverySelectsTheClusterWithBothDatabaseEncodings(): void
    {
        foreach ([new Mysql(), new Pgsql()] as $adapter) {
            $db = $this->createMock(Connection::class);
            $db->method('getAdapter')->willReturn($adapter);
            $db->expects(self::once())->method('fetchAll')->with(
                'SELECT * FROM kubernetes_instance WHERE cluster_uuid = ?',
                [$adapter instanceof Pgsql
                    ? '\\x' . bin2hex(Uuid::fromString(static::OTHER_CLUSTER)->getBytes())
                    : Uuid::fromString(static::OTHER_CLUSTER)->getBytes()],
            )->willReturn([(object) [
                'heartbeat'                            => time() * 1000,
                'kubernetes_service_user'              => 'selected-cluster',
                'notifications_discovered_socket_path' => '/run/selected',
            ]]);
            self::assertSame('selected-cluster', NotificationsDiscovery::load($db, static::OTHER_CLUSTER)->serviceUser);
        }
    }

    #[DataProvider('clusterUuidBindings')]
    public function testSaveBindsTheSameClusterUuidForReadsAndWrites(Adapter $adapter, string $uuid): void
    {
        $service = $this->service($adapter, $uuid);
        $service->expects(self::never())->method('getSource');
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::SOCKET_URL]);

        self::assertSame(static::SOCKET_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
    }

    /**
     * @return array<string, array{Adapter, string}>
     */
    public static function clusterUuidBindings(): array
    {
        $hex = 'd64a88272de649159aad7c75fb32e798';

        return [
            'MySQL'      => [new Mysql(), hex2bin($hex)],
            'PostgreSQL' => [new Pgsql(), '\\x' . $hex],
        ];
    }

    public function testAssistedUnixSetupCreatesPasswordlessSourceAndRetainsHttpCredentials(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => static::CLUSTER,
            Config::NOTIFICATIONS_PASSWORD => 'stored-password',
        ];
        $discovery = $this->discovery();
        $source = $this->source(null);
        $source->expects(self::once())->method('create')->with(Config::DEFAULT_NOTIFICATIONS_NAME, null);

        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with('kubernetes')->willReturn($source);
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::SOCKET_URL], $discovery);

        self::assertSame(static::SOCKET_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame(static::CLUSTER, $this->settings[Config::NOTIFICATIONS_USERNAME]);
        self::assertSame('stored-password', $this->settings[Config::NOTIFICATIONS_PASSWORD]);
    }

    #[DataProvider('staleDiscoveries')]
    public function testStaleDiscoveryDoesNotChangeSettings(string $url, string $path, string $user, int $age): void
    {
        $confirmation = $this->discovery();
        $this->discovery($path, $user, $age);
        $service = $this->service();
        $service->expects(self::never())->method('getSource');

        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => $url], $confirmation);
            self::fail('Stale discovery must reject the save');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Confirm the settings again', $e->getMessage());
        } finally {
            self::assertSame([], $this->deletes);
            self::assertSame([], $this->inserts);
        }
    }

    /**
     * @return array<string, array{string, string, string, int}>
     */
    public static function staleDiscoveries(): array
    {
        return [
            'URL edited'   => ['unix:///run/different.sock', static::SOCKET_PATH, 'kubernetes', 0],
            'path changed' => [static::SOCKET_URL, '/run/different.sock', 'kubernetes', 0],
            'user changed' => [static::SOCKET_URL, static::SOCKET_PATH, 'new-user', 0],
            'expired'      => [static::SOCKET_URL, static::SOCKET_PATH, 'kubernetes', 61],
        ];
    }

    public function testExistingUnixSourceRequiresManualSetupWithoutTypeLookup(): void
    {
        $discovery = $this->discovery();
        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with('kubernetes')
            ->willReturn($this->unchangedSource(Config::DEFAULT_NOTIFICATIONS_NAME));

        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::SOCKET_URL], $discovery);
            self::fail('The assisted save must reject the existing source');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('save the socket URL manually', $e->getMessage());
        }

        self::assertSame([], $this->settings);
        self::assertSame([], $this->deletes);
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::SOCKET_URL]);
        self::assertSame(static::SOCKET_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
    }

    public function testAssistedUnixSetupReusesVerifiedKubernetesSourceWithoutChangingIt(): void
    {
        $discovery = $this->discovery();
        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with('kubernetes')
            ->willReturn($this->unchangedSource('Shared Kubernetes source', true));

        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::SOCKET_URL], $discovery);

        self::assertSame(static::SOCKET_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
    }

    public function testAssistedUnixSetupRejectsAnotherSourceTypeBeforeWritingSettings(): void
    {
        $discovery = $this->discovery();
        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with('kubernetes')
            ->willReturn($this->unchangedSource(Config::DEFAULT_NOTIFICATIONS_NAME, false));

        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::SOCKET_URL], $discovery);
            self::fail('A different source type must reject the assisted save');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('listener username "kubernetes" belongs to a different', $e->getMessage());
        } finally {
            self::assertSame([], $this->deletes);
            self::assertSame([], $this->inserts);
        }
    }

    public function testManualUnixSetupDoesNotRequireDiscoveryOrNotificationsWeb(): void
    {
        $service = $this->service();
        $service->expects(self::never())->method('getSource');
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::SOCKET_URL]);

        self::assertSame(static::SOCKET_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
    }

    public function testUnixRejectsLocallyConfiguredCredentials(): void
    {
        $this->settings[Config::NOTIFICATIONS_USERNAME] = 'local-user';
        $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        $service = $this->service();
        $service->expects(self::never())->method('getSource');

        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::SOCKET_URL]);
            self::fail('A Unix URL with locally configured credentials must reject the save');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Remove locally configured Notifications credentials', $e->getMessage());
        } finally {
            self::assertSame([], $this->deletes);
            self::assertSame([], $this->inserts);
        }
    }

    public function testLocallyConfiguredHttpCredentialsAreUsedWithoutChangingTheSource(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => 'custom-user',
            Config::NOTIFICATIONS_PASSWORD => 'local-password',
        ];
        $this->locks = [
            Config::NOTIFICATIONS_USERNAME => true,
            Config::NOTIFICATIONS_PASSWORD => true,
        ];
        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with('custom-user')
            ->willReturn($this->unchangedSource('Custom Kubernetes source'));
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);

        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame('custom-user', $this->getSetting(Config::NOTIFICATIONS_USERNAME));
        self::assertSame('local-password', $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
    }

    public function testVacantHttpSourceCanUseBothLocallyConfiguredCredentials(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => 'custom-user',
            Config::NOTIFICATIONS_PASSWORD => 'local-password',
        ];
        $this->locks = [
            Config::NOTIFICATIONS_USERNAME => true,
            Config::NOTIFICATIONS_PASSWORD => true,
        ];
        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with('custom-user')
            ->willReturn($this->newHttpSource());
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);

        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame('custom-user', $this->getSetting(Config::NOTIFICATIONS_USERNAME));
        self::assertSame('local-password', $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
    }

    public function testVacantHttpSourceWithLocalUsernameGetsGeneratedPassword(): void
    {
        $this->settings[Config::NOTIFICATIONS_USERNAME] = 'custom-user';
        $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        $service = $this->service();
        $service->expects(self::exactly(3))->method('getSource')->with('custom-user')->willReturnOnConsecutiveCalls(
            $this->source(null),
            $this->newHttpSource(),
            $this->unchangedSource($this->httpSourceName()),
        );
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);

        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame('custom-user', $this->getSetting(Config::NOTIFICATIONS_USERNAME));
        $password = $this->getSetting(Config::NOTIFICATIONS_PASSWORD);
        self::assertIsString($password);
        self::assertMatchesRegularExpression('/^[a-f0-9]{40}$/D', $password);

        $service->save(
            static::CLUSTER,
            [Config::NOTIFICATIONS_URL => static::HTTP_URL],
            confirmedHttpUser: 'custom-user',
        );
    }

    #[DataProvider('localUsernameChanges')]
    public function testVacantHttpSourceGetsFreshPasswordWhenLocalUsernameChanges(?string $newUsername): void
    {
        $this->settings[Config::NOTIFICATIONS_USERNAME] = 'original-user';
        $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        $effectiveUsername = $newUsername ?? static::CLUSTER;
        $usernames = ['original-user', 'original-user', $effectiveUsername, $effectiveUsername];
        $sources = [$this->source(null), $this->newHttpSource(), $this->source(null), $this->newHttpSource()];
        $service = $this->service();
        $service->expects(self::exactly(4))->method('getSource')->willReturnCallback(
            static function (string $username) use (&$usernames, &$sources): NotificationsSource {
                self::assertSame(array_shift($usernames), $username);
                $source = array_shift($sources);
                self::assertInstanceOf(NotificationsSource::class, $source);

                return $source;
            }
        );
        $values = [Config::NOTIFICATIONS_URL => static::HTTP_URL];
        $service->save(static::CLUSTER, $values);
        $previousPassword = $this->getSetting(Config::NOTIFICATIONS_PASSWORD);
        self::assertIsString($previousPassword);

        if ($newUsername === null) {
            unset($this->settings[Config::NOTIFICATIONS_USERNAME], $this->locks[Config::NOTIFICATIONS_USERNAME]);
        } else {
            $this->settings[Config::NOTIFICATIONS_USERNAME] = $newUsername;
        }
        $service->save(static::CLUSTER, $values);

        $password = $this->getSetting(Config::NOTIFICATIONS_PASSWORD);
        self::assertIsString($password);
        self::assertNotSame($previousPassword, $password);
        self::assertSame($effectiveUsername, $this->getSetting(Config::NOTIFICATIONS_USERNAME));
        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function localUsernameChanges(): array
    {
        return [
            'override changed' => ['replacement-user'],
            'override removed' => [null],
        ];
    }

    public function testExistingHttpSourceWithOnlyLocalUsernameRequiresConfirmation(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => 'custom-user',
            Config::NOTIFICATIONS_PASSWORD => 'old-password-for-unverified-source',
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
        ];
        $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        $service = $this->service();
        $service->expects(self::exactly(2))->method('getSource')->with('custom-user')->willReturnOnConsecutiveCalls(
            $this->unchangedSource($this->httpSourceName()),
            $this->unchangedSource($this->httpSourceName()),
        );
        $values = [Config::NOTIFICATIONS_URL => 'https://notifications.example'];

        try {
            $service->save(static::CLUSTER, $values);
            self::fail('A local username alone cannot verify the stored password belongs to this source');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Use an existing HTTP source', $e->getMessage());
        }
        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame([], $this->deletes);
        self::assertSame([], $this->inserts);

        $service->save(static::CLUSTER, $values, confirmedHttpUser: 'custom-user');
        self::assertSame('https://notifications.example', $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame('old-password-for-unverified-source', $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
    }

    public function testReenablingHttpWithLocalUsernameUsesExistingSourceAfterConfirmation(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => 'custom-user',
            Config::NOTIFICATIONS_PASSWORD => 'stored-password',
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
        ];
        $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        $service = $this->service();
        $service->expects(self::exactly(2))->method('getSource')->with('custom-user')->willReturnOnConsecutiveCalls(
            $this->unchangedSource($this->httpSourceName()),
            $this->unchangedSource($this->httpSourceName()),
        );

        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => '']);
        $values = [Config::NOTIFICATIONS_URL => static::HTTP_URL];
        try {
            $service->save(static::CLUSTER, $values);
            self::fail('Re-enabling an existing custom username requires confirmation');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Use an existing HTTP source', $e->getMessage());
        }
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_URL, $this->settings);

        $service->save(static::CLUSTER, $values, confirmedHttpUser: 'custom-user');
        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame('stored-password', $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
    }

    public function testOnlyExplicitRotationChangesTheDedicatedSourcePassword(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => static::CLUSTER,
            Config::NOTIFICATIONS_PASSWORD => 'new-local-password',
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
        ];
        $this->locks[Config::NOTIFICATIONS_PASSWORD] = true;
        $source = $this->source($this->httpSourceName(), true);
        $source->expects(self::once())->method('rotatePassword')->with('new-local-password');
        $source->expects(self::never())->method('create');
        $service = $this->service();
        $service->expects(self::exactly(2))->method('getSource')->with(static::CLUSTER)->willReturnOnConsecutiveCalls(
            $this->unchangedSource($this->httpSourceName(), true),
            $source,
        );
        $values = [Config::NOTIFICATIONS_URL => static::HTTP_URL];

        $service->save(static::CLUSTER, $values);
        $service->save(static::CLUSTER, $values, rotateHttpUser: static::CLUSTER);
    }

    public function testVerifiedHttpSourceTypeDoesNotReplaceCredentialOwnershipChecks(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => static::CLUSTER,
            Config::NOTIFICATIONS_PASSWORD => 'stored-password',
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
        ];
        $service = $this->service();
        $service->expects(self::exactly(2))->method('getSource')->with(static::CLUSTER)->willReturnOnConsecutiveCalls(
            $this->unchangedSource('Manually configured Kubernetes source', true),
            $this->unchangedSource('Manually configured Kubernetes source', true),
        );
        $url = 'https://notifications.example';
        $values = [Config::NOTIFICATIONS_URL => $url];

        try {
            $service->save(static::CLUSTER, $values);
            self::fail('Matching type alone must leave HTTP source reuse unconfirmed');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Use an existing HTTP source', $e->getMessage());
        }
        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));

        $service->save(static::CLUSTER, $values, confirmedHttpUser: static::CLUSTER);
        self::assertSame($url, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame('stored-password', $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
    }

    #[DataProvider('foreignHttpSourceRequests')]
    public function testHttpSetupRejectsAnotherSourceTypeBeforeWritingSettings(
        bool $usernameLocked,
        bool $passwordLocked,
        ?string $confirmedUser,
        ?string $rotateUser,
    ): void {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => static::CLUSTER,
            Config::NOTIFICATIONS_PASSWORD => 'local-password',
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
        ];
        if ($usernameLocked) {
            $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        }
        if ($passwordLocked) {
            $this->locks[Config::NOTIFICATIONS_PASSWORD] = true;
        }
        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with(static::CLUSTER)
            ->willReturn($this->unchangedSource($this->httpSourceName(), false));

        try {
            $service->save(
                static::CLUSTER,
                [Config::NOTIFICATIONS_URL => static::HTTP_URL],
                confirmedHttpUser: $confirmedUser,
                rotateHttpUser: $rotateUser,
            );
            self::fail('A different source type must reject the HTTP save');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('belongs to a different source type', $e->getMessage());
        } finally {
            self::assertSame([], $this->deletes);
            self::assertSame([], $this->inserts);
        }
    }

    /**
     * @return array<string, array{bool, bool, ?string, ?string}>
     */
    public static function foreignHttpSourceRequests(): array
    {
        return [
            'stored credentials'           => [false, false, null, null],
            'locally supplied credentials' => [true, true, null, null],
            'confirmed reuse'              => [false, false, static::CLUSTER, null],
            'password rotation'            => [false, true, null, static::CLUSTER],
        ];
    }

    #[DataProvider('ambiguousRotationRequests')]
    public function testPasswordRotationRejectsAmbiguousIdentity(string $username, ?string $name, bool $locked): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => static::CLUSTER,
            Config::NOTIFICATIONS_PASSWORD => 'configured-password',
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
        ];
        if ($locked) {
            $this->locks[Config::NOTIFICATIONS_PASSWORD] = true;
        }
        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with(static::CLUSTER)
            ->willReturn($this->unchangedSource($name));

        try {
            $service->save(
                static::CLUSTER,
                [Config::NOTIFICATIONS_URL => static::HTTP_URL],
                rotateHttpUser: $username,
            );
            self::fail('An ambiguous identity must reject rotation');
        } catch (RuntimeException $e) {
            self::assertStringContainsString(
                'Password rotation requires the dedicated Kubernetes source',
                $e->getMessage(),
            );
        } finally {
            self::assertSame([], $this->deletes);
            self::assertSame([], $this->inserts);
        }
    }

    /**
     * @return array<string, array{string, ?string, bool}>
     */
    public static function ambiguousRotationRequests(): array
    {
        $expectedName = 'Icinga for Kubernetes (' . static::CLUSTER . ')';

        return [
            'stale username'    => [static::OTHER_CLUSTER, $expectedName, true],
            'shared source'     => [static::CLUSTER, 'Icinga for Kubernetes', true],
            'missing source'    => [static::CLUSTER, null, true],
            'unlocked password' => [static::CLUSTER, $expectedName, false],
        ];
    }

    public function testLockedUrlLeavesSourceAndCredentialsUntouched(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
            Config::NOTIFICATIONS_PASSWORD => 'locked-transport-password',
        ];
        $this->locks[Config::NOTIFICATIONS_URL] = true;
        $service = $this->service();
        $service->expects(self::never())->method('getSource');
        $service->save(static::CLUSTER, [
            Config::NOTIFICATIONS_URL            => static::SOCKET_URL,
            Config::NOTIFICATIONS_ICINGAWEB2_URL => 'https://example.com/icingaweb2',
        ]);

        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame('locked-transport-password', $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
        self::assertSame('https://example.com/icingaweb2', $this->getSetting(Config::NOTIFICATIONS_ICINGAWEB2_URL));
    }

    public function testNewHttpSourceUsesGeneratedPasswordAndRetainsItAcrossTransportChanges(): void
    {
        $service = $this->service();
        $service->expects(self::exactly(4))->method('getSource')->with(static::CLUSTER)
            ->willReturnOnConsecutiveCalls(
                $this->source(null),
                $this->newHttpSource(),
                $this->unchangedSource($this->httpSourceName()),
                $this->unchangedSource($this->httpSourceName()),
            );

        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
        $password = $this->getSetting(Config::NOTIFICATIONS_PASSWORD);
        self::assertIsString($password);
        self::assertMatchesRegularExpression('/^[a-f0-9]{40}$/D', $password);
        self::assertSame(static::CLUSTER, $this->getSetting(Config::NOTIFICATIONS_USERNAME));
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::SOCKET_URL]);
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => 'https://notifications.example']);
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => '']);

        self::assertArrayNotHasKey(Config::NOTIFICATIONS_URL, $this->settings);
        self::assertSame($password, $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
        self::assertSame(static::CLUSTER, $this->getSetting(Config::NOTIFICATIONS_USERNAME));
    }

    public function testForeignHttpSourceIsRefusedBeforeAnyMutation(): void
    {
        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with(static::CLUSTER)
            ->willReturn($this->unchangedSource('Unrelated source'));

        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
            self::fail('The existing source must be rejected');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('A Notifications source already uses', $e->getMessage());
        } finally {
            self::assertSame([], $this->deletes);
            self::assertSame([], $this->inserts);
        }
    }

    public function testConcurrentHttpClaimRollsBackActivationAndIsRefusedOnRetry(): void
    {
        $service = $this->service();
        $newSource = $this->newHttpSource(throws: new RuntimeException('Duplicate listener username'));
        $service->expects(self::exactly(3))->method('getSource')->with(static::CLUSTER)->willReturnOnConsecutiveCalls(
            $this->source(null),
            $newSource,
            $this->unchangedSource($this->httpSourceName()),
        );

        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
            self::fail('A concurrent claim must fail');
        } catch (RuntimeException $e) {
            self::assertSame('Duplicate listener username', $e->getMessage());
        }

        self::assertArrayNotHasKey(Config::NOTIFICATIONS_URL, $this->settings);
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_USERNAME, $this->settings);
        self::assertArrayHasKey(Config::NOTIFICATIONS_PASSWORD, $this->settings);
        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
            self::fail('A retry must reject the concurrently claimed source');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('A Notifications source already uses', $e->getMessage());
        }
    }

    public function testNetworkFailureBeforeHttpSourceCreationKeepsUnixActiveAndAllowsRetry(): void
    {
        $this->settings[Config::NOTIFICATIONS_URL] = static::SOCKET_URL;
        $service = $this->service();
        $service->expects(self::exactly(4))->method('getSource')->with(static::CLUSTER)->willReturnOnConsecutiveCalls(
            $this->source(null),
            $this->newHttpSource(throws: new RuntimeException('Notifications database unavailable')),
            $this->source(null),
            $this->newHttpSource(),
        );

        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
            self::fail('The source save must fail');
        } catch (RuntimeException $e) {
            self::assertSame('Notifications database unavailable', $e->getMessage());
        }

        $password = $this->getSetting(Config::NOTIFICATIONS_PASSWORD);
        self::assertIsString($password);
        self::assertSame(static::SOCKET_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_USERNAME, $this->settings);
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
        self::assertNotSame($password, $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
    }

    #[DataProvider('httpActivationFailures')]
    public function testHttpActivationCanResumeAfterItsCommitFailsWithExplicitConfirmation(
        string $username,
        bool $usernameLocked,
    ): void {
        $this->settings = [
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
            Config::NOTIFICATIONS_USERNAME => $username,
            Config::NOTIFICATIONS_PASSWORD => 'old-web-password',
        ];
        if ($usernameLocked) {
            $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        }
        $this->failCommit = 2;
        $service = $this->service();
        $service->expects(self::exactly(5))->method('getSource')->with($username)->willReturnOnConsecutiveCalls(
            $this->source(null),
            $this->newHttpSource(),
            $this->unchangedSource($this->httpSourceName()),
            $this->unchangedSource($this->httpSourceName()),
            $this->unchangedSource($this->httpSourceName()),
        );
        $values = [Config::NOTIFICATIONS_URL => static::HTTP_URL];

        try {
            $service->save(static::CLUSTER, $values);
            self::fail('The activation must fail');
        } catch (RuntimeException $e) {
            self::assertSame('Simulated Kubernetes commit failure', $e->getMessage());
        }

        $password = $this->getSetting(Config::NOTIFICATIONS_PASSWORD);
        self::assertIsString($password);
        self::assertNotSame('old-web-password', $password);
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_URL, $this->settings);
        self::assertSame($usernameLocked ? $username : null, $this->getSetting(Config::NOTIFICATIONS_USERNAME));

        $unconfirmedAttempts = [
            'A Notifications source already uses' => null,
            'The confirmed HTTP source or its stored credentials are unavailable' => static::OTHER_CLUSTER,
        ];
        foreach ($unconfirmedAttempts as $message => $confirmedUsername) {
            try {
                $service->save(static::CLUSTER, $values, confirmedHttpUser: $confirmedUsername);
                self::fail('The existing source requires explicit confirmation of the correct username');
            } catch (RuntimeException $e) {
                self::assertStringContainsString($message, $e->getMessage());
                self::assertArrayNotHasKey(Config::NOTIFICATIONS_URL, $this->settings);
            }
        }

        $service->save(static::CLUSTER, $values, confirmedHttpUser: $username);
        self::assertSame($password, $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame($username, $this->getSetting(Config::NOTIFICATIONS_USERNAME));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function httpActivationFailures(): array
    {
        return [
            'generated username' => [static::CLUSTER, false],
            'locked username'    => ['custom-user', true],
        ];
    }

    public function testHttpSetupRejectsAUsernameClaimedBetweenStagingAndProvisioning(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
            Config::NOTIFICATIONS_USERNAME => 'custom-user',
            Config::NOTIFICATIONS_PASSWORD => 'old-web-password',
        ];
        $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        $service = $this->service();
        $service->expects(self::exactly(3))->method('getSource')->with('custom-user')->willReturnOnConsecutiveCalls(
            $this->source(null),
            $this->unchangedSource($this->httpSourceName(), true),
            $this->unchangedSource($this->httpSourceName(), true),
        );
        for ($attempt = 0; $attempt < 2; ++$attempt) {
            try {
                $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
                self::fail('An unconfirmed claim must reject the save');
            } catch (RuntimeException $e) {
                self::assertStringContainsString('A Notifications source already uses', $e->getMessage());
                self::assertArrayNotHasKey(Config::NOTIFICATIONS_URL, $this->settings);
            }
        }
    }

    #[DataProvider('stagedSettingChanges')]
    public function testStagedHttpSetupRejectsInterleavedSettingChanges(
        string $key,
        ?string $value,
        ?bool $locked,
    ): void {
        $this->settings[Config::NOTIFICATIONS_USERNAME] = 'custom-user';
        $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        $service = $this->service(afterCommit: function () use ($key, $value, $locked): void {
            if ($value !== null) {
                $this->settings[$key] = $value;
            }
            if ($locked === true) {
                $this->locks[$key] = true;
            } elseif ($locked === false) {
                unset($this->locks[$key]);
            }
        });
        $service->expects(self::once())->method('getSource')->with('custom-user')->willReturn($this->source(null));

        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
            self::fail('An interleaved change must reject the staged save');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('settings changed during source setup', $e->getMessage());
        }

        self::assertSame(
            $key === Config::NOTIFICATIONS_URL ? $value : null,
            $this->getSetting(Config::NOTIFICATIONS_URL),
        );
        self::assertCount(1, $this->deletes);
        self::assertCount(1, $this->inserts);
    }

    /**
     * @return array<string, array{string, ?string, ?bool}>
     */
    public static function stagedSettingChanges(): array
    {
        return [
            'daemon changes username' => [Config::NOTIFICATIONS_USERNAME, 'another-user', null],
            'daemon unlocks username' => [Config::NOTIFICATIONS_USERNAME, null, false],
            'daemon locks password'   => [Config::NOTIFICATIONS_PASSWORD, null, true],
            'another save stages'     => [Config::NOTIFICATIONS_PASSWORD, 'interleaved-password', null],
            'another save uses Unix'  => [Config::NOTIFICATIONS_URL, static::SOCKET_URL, null],
        ];
    }

    public function testNotificationsV1ProvidesThePublicSourceMethods(): void
    {
        foreach (['get', 'getName', 'setName', 'setType', 'setPassword', 'save'] as $method) {
            self::assertTrue((new ReflectionMethod(Source::class, $method))->isPublic());
        }
        self::assertTrue((new ReflectionMethod(Source::class, 'get'))->isStatic());
    }

    protected function getSetting(string $key): ?string
    {
        return $this->settings[$key] ?? null;
    }

    protected function newHttpSource(?RuntimeException $throws = null): NotificationsSource
    {
        $source = $this->source(null);
        $save = $source->expects(self::once())->method('create')->with(
            $this->httpSourceName(),
            self::callback(
                fn(string $password): bool => $password === $this->getSetting(Config::NOTIFICATIONS_PASSWORD)
            ),
        );
        if ($throws !== null) {
            $save->willThrowException($throws);
        }

        return $source;
    }

    protected function unchangedSource(?string $name, ?bool $isKubernetes = null): NotificationsSource
    {
        $source = $this->source($name, $isKubernetes);
        $source->expects(self::never())->method('create');
        $source->expects(self::never())->method('rotatePassword');

        return $source;
    }

    protected function source(?string $name, ?bool $isKubernetes = null): NotificationsSource&MockObject
    {
        $source = $this->createMock(NotificationsSource::class);
        $source->expects(self::once())->method('getName')->willReturn($name);
        $source->method('isKubernetes')->willReturn($isKubernetes);

        return $source;
    }

    protected function httpSourceName(): string
    {
        return 'Icinga for Kubernetes (' . static::CLUSTER . ')';
    }

    protected function discovery(
        string $path = self::SOCKET_PATH,
        string $user = 'kubernetes',
        int $age = 0,
    ): string {
        $this->instances = [(object) [
            'heartbeat'                            => (time() - $age) * 1000,
            'notifications_discovered_socket_path' => $path,
            'kubernetes_service_user'              => $user,
        ]];

        return hash('sha256', static::CLUSTER . "\0" . $path . "\0" . $user);
    }

    protected function service(
        Adapter $adapter = new Mysql(),
        ?string $uuid = null,
        ?Closure $afterCommit = null,
    ): NotificationsConfig&MockObject {
        $uuid ??= Uuid::fromString(static::CLUSTER)->getBytes();
        $quotedKey = $adapter->quoteIdentifier('key');
        $db = $this->createStub(Connection::class);
        $db->method('getAdapter')->willReturn($adapter);
        $db->method('quoteIdentifier')->willReturnCallback(
            static function (string $identifier) use ($quotedKey): string {
                self::assertSame('key', $identifier);

                return $quotedKey;
            }
        );
        $db->method('fetchOne')->willReturnCallback(static function (string $sql, array $params) use ($uuid): object {
            self::assertSame('SELECT uuid FROM cluster WHERE uuid = ? FOR UPDATE', $sql);
            self::assertSame([$uuid], $params);

            return (object) ['uuid' => $uuid];
        });
        $db->method('fetchAll')->willReturnCallback(function (string $query, array $params) use ($uuid): array {
            if (str_contains($query, 'FROM kubernetes_instance')) {
                self::assertSame([$uuid], $params);

                return $this->instances;
            }

            self::assertSame($uuid, $params[4]);
            $settings = [];
            foreach ($this->settings as $key => $value) {
                $settings[] = (object) [
                    'key'    => $key,
                    'value'  => $value,
                    'locked' => isset($this->locks[$key]) ? 'y' : 'n',
                ];
            }

            return $settings;
        });
        $db->method('transaction')->willReturnCallback(function (callable $save) use ($db, $afterCommit): mixed {
            $original = $this->settings;
            ++$this->transactions;
            try {
                $result = $save($db);
                if ($this->transactions === $this->failCommit) {
                    throw new RuntimeException('Simulated Kubernetes commit failure');
                }
            } catch (RuntimeException $e) {
                $this->settings = $original;

                throw $e;
            }

            $afterCommit?->__invoke();

            return $result;
        });
        $db->method('delete')->willReturnCallback(
            function (string $table, array $where) use ($quotedKey, $uuid): PDOStatement {
                self::assertSame('config', $table);
                self::assertSame($uuid, $where['cluster_uuid = ?']);
                $this->deletes[] = $where;
                foreach ($where["$quotedKey IN (?)"] as $key) {
                    self::assertArrayNotHasKey($key, $this->locks);
                    unset($this->settings[$key]);
                }

                return $this->createStub(PDOStatement::class);
            }
        );
        $db->method('insert')->willReturnCallback(
            function (string $table, array $data) use ($quotedKey, $uuid): PDOStatement {
                self::assertSame('config', $table);
                self::assertSame('n', $data['locked']);
                self::assertSame($uuid, $data['cluster_uuid']);
                $this->inserts[] = $data;
                $this->settings[$data[$quotedKey]] = $data['value'];

                return $this->createStub(PDOStatement::class);
            }
        );

        return $this->getMockBuilder(NotificationsConfig::class)
            ->setConstructorArgs([$db])
            ->onlyMethods(['getSource'])
            ->getMock();
    }
}

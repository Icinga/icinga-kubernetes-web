<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Test;

use Closure;
use Icinga\Module\Kubernetes\Common\NotificationsConfig;
use Icinga\Module\Kubernetes\Common\NotificationsDiscovery;
use Icinga\Module\Kubernetes\Common\NotificationsUrl;
use Icinga\Module\Kubernetes\Model\Config;
use Icinga\Module\Notifications\Form\Data\Source as SourceData;
use Icinga\Module\Notifications\Model\Source as SourceModel;
use Icinga\Module\Notifications\Repository\SourceRepository;
use ipl\Sql\Adapter\Mysql;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class NotificationsConfigTest extends TestCase
{
    /** @var string */
    protected const CLUSTER = 'd64a8827-2de6-4915-9aad-7c75fb32e798';

    /** @var string */
    protected const OTHER_CLUSTER = '471d54de-dc99-4d5a-b794-a1a17d847980';

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
            'IDN hostname'      => ['https://über.example/events', true, true],
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

    public function testUnixSaveRemovesHttpCredentialsAndCreatesPasswordlessSource(): void
    {
        $repo = $this->createMock(SourceRepository::class);
        $repo->method('findByUsername')->willReturn(null);
        $repo->expects(self::once())->method('create')->willReturnCallback(function (SourceData $source): int {
            self::assertSame('kubernetes', $source->listenerUsername);
            self::assertSame('kubernetes', $source->type);
            self::assertSame('Icinga for Kubernetes', $source->name);
            self::assertNull($source->listenerPassword);
            self::assertTrue($source->locked);

            return 1;
        });
        $service = $this->service($this->database(), $repo);
        self::assertTrue($service->save(static::CLUSTER, [
            Config::NOTIFICATIONS_USERNAME => $this->setting(static::CLUSTER),
            Config::NOTIFICATIONS_PASSWORD => $this->setting('old-http-secret'),
        ], [Config::NOTIFICATIONS_URL => 'unix:///run/socket'], 'kubernetes'));
        self::assertContains(Config::NOTIFICATIONS_USERNAME, $this->deletes[0]['`key` IN (?)']);
        self::assertContains(Config::NOTIFICATIONS_PASSWORD, $this->deletes[0]['`key` IN (?)']);
        self::assertSame([Config::NOTIFICATIONS_URL], array_column($this->inserts, '`key`'));
    }

    public function testUnixReuseAndDisablingAnotherClusterNeverChangeSharedSourceCredentials(): void
    {
        $existing = new SourceModel(['id' => 1, 'name' => 'Shared Kubernetes', 'type' => 'kubernetes',
            'listener_password_hash'      => 'existing-http-hash']);
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::atLeastOnce())->method('findByUsername')->with('kubernetes')->willReturn($existing);
        $repo->expects(self::never())->method('update');
        $repo->expects(self::never())->method('create');
        $repo->expects(self::never())->method('delete');
        $service = $this->service($this->database(), $repo);
        foreach ([static::CLUSTER, static::OTHER_CLUSTER] as $cluster) {
            self::assertTrue($service->save(
                $cluster,
                [],
                [Config::NOTIFICATIONS_URL => 'unix:///run/socket'],
                'kubernetes',
            ));
        }
        self::assertTrue($service->save(static::CLUSTER, [], [Config::NOTIFICATIONS_URL => ''], 'kubernetes'));
        self::assertSame('existing-http-hash', $existing->listener_password_hash);
    }

    public function testManualUnixConfigurationWorksWithoutDiscovery(): void
    {
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::never())->method('findByUsername');
        $repo->expects(self::never())->method('create');
        $service = $this->service($this->database(), $repo);
        self::assertFalse($service->save(
            static::CLUSTER,
            [],
            [Config::NOTIFICATIONS_URL => 'unix:///run/manual'],
            null,
        ));
        self::assertSame('unix:///run/manual', $this->inserts[0]['value']);
    }

    public function testUnixLocalCredentialConflictsFailBeforeAnyMutation(): void
    {
        $lockedSettings = [
            [Config::NOTIFICATIONS_USERNAME, 'local'],
            [Config::NOTIFICATIONS_USERNAME, '0'],
            [Config::NOTIFICATIONS_PASSWORD, 'local'],
            [Config::NOTIFICATIONS_PASSWORD, '0'],
        ];
        foreach ($lockedSettings as [$lockedKey, $lockedValue]) {
            $repo = $this->createMock(SourceRepository::class);
            $repo->expects(self::never())->method('findByUsername');
            $repo->expects(self::never())->method('create');
            $service = $this->service($this->database(), $repo);
            try {
                $service->save(static::CLUSTER, [$lockedKey => $this->setting($lockedValue, true)], [
                    Config::NOTIFICATIONS_URL => 'unix:///run/socket',
                ], 'kubernetes');
                self::fail('Expected a locked credential conflict');
            } catch (RuntimeException $e) {
                self::assertStringContainsString('locally configured', $e->getMessage());
            }
        }
        self::assertSame([], $this->deletes);
        self::assertSame([], $this->inserts);
    }

    public function testForeignSourceCollisionFailsBeforeAnyMutation(): void
    {
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::atLeastOnce())->method('findByUsername')->with('icinga')->willReturn(
            new SourceModel(['type' => 'icinga2', 'name' => 'Icinga DB']),
        );
        $repo->expects(self::never())->method('create');
        $repo->expects(self::never())->method('update');
        $service = $this->service($this->database(), $repo);
        try {
            $service->save(static::CLUSTER, [], [Config::NOTIFICATIONS_URL => 'unix:///run/socket'], 'icinga');
            self::fail('Expected a source identity conflict');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('another source type', $e->getMessage());
        }
        self::assertSame([], $this->deletes);
        self::assertSame([], $this->inserts);
    }

    public function testLockedUrlLeavesSourceAndAuthenticationUntouched(): void
    {
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::never())->method('findByUsername');
        $service = $this->service($this->database(), $repo);
        self::assertTrue($service->save(static::CLUSTER, [
            Config::NOTIFICATIONS_URL => $this->setting('http://locally-configured:5680', true),
        ], [
            Config::NOTIFICATIONS_URL            => 'unix:///run/forged',
            Config::NOTIFICATIONS_USERNAME       => 'forged-user',
            Config::NOTIFICATIONS_PASSWORD       => 'forged-password',
            Config::NOTIFICATIONS_ICINGAWEB2_URL => 'https://web.example',
        ], 'kubernetes'));
        self::assertSame([Config::NOTIFICATIONS_ICINGAWEB2_URL], $this->deletes[0]['`key` IN (?)']);
        self::assertSame([Config::NOTIFICATIONS_ICINGAWEB2_URL], array_column($this->inserts, '`key`'));
    }

    public function testSwitchingFromUnixToHttpCreatesPasswordAndHonorsCredentialLocks(): void
    {
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::atLeastOnce())->method('findByUsername')->with(static::CLUSTER)->willReturn(null);
        $password = null;
        $repo->expects(self::once())->method('create')->willReturnCallback(
            function (SourceData $source) use (&$password): int {
                self::assertSame(static::CLUSTER, $source->listenerUsername);
                self::assertNotEmpty($source->listenerPassword);
                $password = $source->listenerPassword;

                return 1;
            },
        );
        $service = $this->service($this->database(), $repo);
        $service->save(static::CLUSTER, [
            Config::NOTIFICATIONS_URL => $this->setting('unix:///run/socket'),
        ], [Config::NOTIFICATIONS_URL => 'https://notifications.example'], 'kubernetes');
        self::assertSame($password, array_column($this->inserts, 'value', '`key`')[Config::NOTIFICATIONS_PASSWORD]);

        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::atLeastOnce())->method('findByUsername')->with('local-user')->willReturn(null);
        $repo->expects(self::once())->method('create')->willReturnCallback(function (SourceData $source): int {
            self::assertSame('local-user', $source->listenerUsername);
            self::assertSame('local-password', $source->listenerPassword);

            return 2;
        });
        $this->inserts = [];
        $this->service($this->database(), $repo)->save(static::CLUSTER, [
            Config::NOTIFICATIONS_USERNAME => $this->setting('local-user', true),
            Config::NOTIFICATIONS_PASSWORD => $this->setting('local-password', true),
        ], [Config::NOTIFICATIONS_URL => 'http://notifications.example'], null);
        self::assertSame([Config::NOTIFICATIONS_URL], array_column($this->inserts, '`key`'));
    }

    /**
     * @return array<string, array{?string, bool, bool, ?string}>
     */
    public static function unmanagedMissingPasswords(): array
    {
        return [
            'shared local username'        => ['kubernetes', false, true, 'existing-hash'],
            'local cluster username'       => [static::CLUSTER, false, true, 'existing-hash'],
            'locally locked password'      => [null, true, true, 'existing-hash'],
            'unlocked source'              => [null, false, false, 'existing-hash'],
            'passwordless local username'  => ['kubernetes', false, true, null],
            'passwordless unlocked source' => [null, false, false, null],
        ];
    }

    #[DataProvider('unmanagedMissingPasswords')]
    public function testUnmanagedIdentityWithMissingPasswordFailsBeforeMutation(
        ?string $localUsername,
        bool $passwordLocked,
        bool $sourceLocked,
        ?string $passwordHash,
    ): void {
        $config = [];
        if ($localUsername !== null) {
            $config[Config::NOTIFICATIONS_USERNAME] = $this->setting($localUsername, true);
        }
        if ($passwordLocked) {
            $config[Config::NOTIFICATIONS_PASSWORD] = $this->setting('', true);
        }
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::once())->method('findByUsername')->with($localUsername ?? static::CLUSTER)->willReturn(
            new SourceModel([
                'id'                     => 1,
                'type'                   => 'kubernetes',
                'listener_password_hash' => $passwordHash,
                'locked'                 => $sourceLocked,
            ]),
        );
        $repo->expects(self::never())->method('update');
        $service = $this->service($this->database(), $repo);
        try {
            $service->save(
                static::CLUSTER,
                $config,
                [Config::NOTIFICATIONS_URL => 'https://notifications.example'],
                null,
            );
            self::fail('Expected a credential conflict');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('password is unavailable', $e->getMessage());
        }
        self::assertSame([], $this->deletes);
        self::assertSame([], $this->inserts);
    }

    public function testHttpUnixHttpRoundTripRotatesManagedPasswordAndRetainsBothSources(): void
    {
        /** @var array<string, Config> $config */
        $config = [];
        /** @var array<string, SourceModel> $sources */
        $sources = [];
        $updatedIds = [];
        $db = $this->createStub(Connection::class);
        $db->method('quoteIdentifier')->willReturn('`key`');
        $db->method('transaction')->willReturnCallback(static fn(callable $callback) => $callback($db));
        $db->method('delete')->willReturnCallback(function ($table, array $where) use (&$config): PDOStatement {
            self::assertSame('config', $table);
            self::assertSame(Uuid::fromString(static::CLUSTER)->getBytes(), $where['cluster_uuid = ?']);
            self::assertSame('n', $where['locked = ?']);
            foreach ($where['`key` IN (?)'] as $key) {
                unset($config[$key]);
            }

            return $this->createStub(PDOStatement::class);
        });
        $db->method('insert')->willReturnCallback(function ($table, array $data) use (&$config): PDOStatement {
            self::assertSame('config', $table);
            $config[$data['`key`']] = $this->setting($data['value']);

            return $this->createStub(PDOStatement::class);
        });
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::exactly(3))->method('findByUsername')->willReturnCallback(
            static function (string $username) use (&$sources): ?SourceModel {
                return $sources[$username] ?? null;
            },
        );
        $repo->expects(self::exactly(2))->method('create')->willReturnCallback(
            static function (SourceData $source) use (&$sources): int {
                $id = count($sources) + 1;
                $sources[$source->listenerUsername] = new SourceModel([
                    'id'                     => $id,
                    'type'                   => $source->type,
                    'name'                   => $source->name,
                    'listener_username'      => $source->listenerUsername,
                    'listener_password_hash' => $source->listenerPassword === null
                        ? null : SourceRepository::hashPassword($source->listenerPassword),
                    'locked' => $source->locked,
                ]);

                return $id;
            },
        );
        $repo->expects(self::once())->method('update')->willReturnCallback(
            static function (SourceData $source) use (&$sources, &$updatedIds): void {
                self::assertNotNull($source->listenerPassword);
                self::assertSame($sources[$source->listenerUsername]->id, $source->id);
                $updatedIds[] = $source->id;
                $sources[$source->listenerUsername]->listener_password_hash
                    = SourceRepository::hashPassword($source->listenerPassword);
            },
        );
        $repo->expects(self::never())->method('delete');
        $service = $this->service($db, $repo);
        $service->save(static::CLUSTER, $config, [Config::NOTIFICATIONS_URL => 'http://notifications:5680'], null);
        $httpId = $sources[static::CLUSTER]->id;
        $httpHash = $sources[static::CLUSTER]->listener_password_hash;
        $password = $config[Config::NOTIFICATIONS_PASSWORD]->value;
        self::assertTrue(password_verify($password, $httpHash));

        $service->save(static::CLUSTER, $config, [Config::NOTIFICATIONS_URL => 'unix:///run/socket'], 'kubernetes');
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_USERNAME, $config);
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_PASSWORD, $config);
        self::assertCount(2, $sources);
        self::assertSame($httpHash, $sources[static::CLUSTER]->listener_password_hash);
        self::assertNull($sources['kubernetes']->listener_password_hash);
        $unixId = $sources['kubernetes']->id;

        $service->save(static::CLUSTER, $config, [Config::NOTIFICATIONS_URL => 'http://notifications:5680'], null);
        self::assertSame($httpId, $sources[static::CLUSTER]->id);
        self::assertSame($unixId, $sources['kubernetes']->id);
        self::assertNull($sources['kubernetes']->listener_password_hash);
        self::assertSame([$httpId], $updatedIds);
        self::assertCount(2, $sources);
        $newPassword = $config[Config::NOTIFICATIONS_PASSWORD]->value;
        self::assertNotSame($password, $newPassword);
        self::assertNotSame($httpHash, $sources[static::CLUSTER]->listener_password_hash);
        self::assertTrue(password_verify($newPassword, $sources[static::CLUSTER]->listener_password_hash));
        self::assertFalse(password_verify($password, $sources[static::CLUSTER]->listener_password_hash));
    }

    public function testInvalidUrlFailsBeforeAnyMutation(): void
    {
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::never())->method('findByUsername');
        $service = $this->service($this->database(), $repo);
        try {
            $service->save(static::CLUSTER, [], [Config::NOTIFICATIONS_URL => 'unix://host/run/socket'], 'kubernetes');
            self::fail('Expected an invalid URL');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Invalid', $e->getMessage());
        }
        self::assertSame([], $this->deletes);
        self::assertSame([], $this->inserts);
    }

    public function testHttpSaveKeepsExistingPasswordAndChecksForeignSourceType(): void
    {
        $repo = $this->createMock(SourceRepository::class);
        $repo->method('findByUsername')->willReturn(new SourceModel([
            'id' => 42, 'type' => 'kubernetes', 'name' => 'HTTP cluster', 'listener_password_hash' => 'old-hash',
        ]));
        $repo->expects(self::once())->method('update')->willReturnCallback(function (SourceData $source): void {
            self::assertSame('current-password', $source->listenerPassword);
            self::assertSame(42, $source->id);
        });
        $this->service($this->database(), $repo)->save(static::CLUSTER, [
            Config::NOTIFICATIONS_PASSWORD => $this->setting('current-password'),
        ], [Config::NOTIFICATIONS_URL => 'https://notifications.example'], null);

        $this->inserts = [];
        $this->deletes = [];
        $repo = $this->createMock(SourceRepository::class);
        $repo->method('findByUsername')->willReturn(new SourceModel(['type' => 'icinga2', 'name' => 'Icinga DB']));
        $repo->expects(self::never())->method('update');
        try {
            $this->service($this->database(), $repo)->save(static::CLUSTER, [
                Config::NOTIFICATIONS_USERNAME => $this->setting('icinga', true),
                Config::NOTIFICATIONS_PASSWORD => $this->setting('password', true),
            ], [Config::NOTIFICATIONS_URL => 'http://notifications.example'], null);
            self::fail('Expected a source type conflict');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('another source type', $e->getMessage());
        }
        self::assertSame([], $this->deletes);
        self::assertSame([], $this->inserts);
    }

    public function testSourceSaveUsesOnlyTheValidatedIdentitySnapshot(): void
    {
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::once())->method('findByUsername')->with(static::CLUSTER)->willReturn(
            new SourceModel(['id' => 42, 'type' => 'kubernetes', 'name' => 'Validated source']),
        );
        $repo->expects(self::once())->method('update')->willReturnCallback(function (SourceData $source): void {
            self::assertSame(42, $source->id);
            self::assertSame('Validated source', $source->name);
            self::assertSame('kubernetes', $source->type);
        });
        $repo->expects(self::never())->method('create');
        $this->service($this->database(), $repo)->save(static::CLUSTER, [
            Config::NOTIFICATIONS_PASSWORD => $this->setting('current-password'),
        ], [Config::NOTIFICATIONS_URL => 'https://notifications.example'], null);
    }

    public function testConcurrentSourceCreationRejectsInsertAndRollsBackClusterConfiguration(): void
    {
        $repo = $this->createMock(SourceRepository::class);
        $repo->expects(self::once())->method('findByUsername')->with('kubernetes')->willReturn(null);
        $repo->expects(self::never())->method('update');
        $repo->expects(self::once())->method('create')->willReturnCallback(function (SourceData $source): int {
            self::assertNull($source->id);
            self::assertSame('kubernetes', $source->listenerUsername);

            throw new RuntimeException('Unique username already claimed by another source');
        });
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods([
            'beginTransaction', 'commitTransaction', 'rollBackTransaction', 'quoteIdentifier', 'delete', 'insert',
        ])->getMock();
        $db->expects(self::once())->method('beginTransaction')->willReturn(true);
        $db->expects(self::never())->method('commitTransaction');
        $db->expects(self::once())->method('rollBackTransaction')->willReturn(true);
        $db->method('quoteIdentifier')->willReturn('`key`');
        $db->expects(self::once())->method('delete')->willReturn($this->createStub(PDOStatement::class));
        $db->expects(self::once())->method('insert')->willReturn($this->createStub(PDOStatement::class));
        $this->expectException(RuntimeException::class);
        $this->service($db, $repo)->save(static::CLUSTER, [], [
            Config::NOTIFICATIONS_URL => 'unix:///run/socket',
        ], 'kubernetes');
    }

    protected function service(Connection $db, SourceRepository $sources): NotificationsConfig
    {
        return new class ($db, $sources) extends NotificationsConfig {
            public function __construct(Connection $db, protected readonly SourceRepository $sources)
            {
                parent::__construct($db);
            }

            protected function getSourceRepository(): SourceRepository
            {
                return $this->sources;
            }

            protected function getSourceTransaction(): Closure
            {
                return static fn(callable $callback) => $callback();
            }
        };
    }

    protected function setting(string $value, bool $locked = false): Config
    {
        return new Config(['value' => $value, 'locked' => $locked]);
    }

    protected function database(): Connection
    {
        $db = $this->createStub(Connection::class);
        $db->method('quoteIdentifier')->willReturn('`key`');
        $db->method('transaction')->willReturnCallback(static fn(callable $callback) => $callback($db));
        $db->method('delete')->willReturnCallback(function ($table, array $where): PDOStatement {
            self::assertSame('config', $table);
            self::assertSame('n', $where['locked = ?']);
            $this->deletes[] = $where;

            return $this->createStub(PDOStatement::class);
        });
        $db->method('insert')->willReturnCallback(function ($table, $data): PDOStatement {
            self::assertSame('config', $table);
            $this->inserts[] = $data;

            return $this->createStub(PDOStatement::class);
        });

        return $db;
    }
}

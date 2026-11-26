<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Test;

use Icinga\Module\Kubernetes\Common\NotificationsConfig;
use Icinga\Module\Kubernetes\Common\NotificationsSource;
use Icinga\Module\Kubernetes\Common\NotificationsUrl;
use Icinga\Module\Kubernetes\Model\Config;
use Icinga\Module\Notifications\Integrations\V1\Source;
use ipl\Sql\Adapter\Mysql;
use ipl\Sql\Connection;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use RuntimeException;

class NotificationsConfigTest extends TestCase
{
    /** @var string */
    protected const CLUSTER = 'd64a8827-2de6-4915-9aad-7c75fb32e798';

    /** @var string */
    protected const OTHER_CLUSTER = '471d54de-dc99-4d5a-b794-a1a17d847980';

    /** @var string */
    protected const HTTP_URL = 'http://notifications.example:5680';

    /** @var array<string, string> */
    protected array $settings = [];

    /** @var array<string, bool> */
    protected array $locks = [];

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
            'IDN hostname'      => ['https://über.example/events', true, true],
            'raw newline'       => ["http://notifications.example/\n", false, false],
            'host whitespace'   => ['http://invalid host:5680', false, false],
            'relative URL'      => ['/run/socket', false, false],
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

        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
    }

    public function testLegacyHttpSourceWithLocalUsernameIsLeftUntouched(): void
    {
        $this->settings = [
            Config::NOTIFICATIONS_USERNAME => 'custom-user',
            Config::NOTIFICATIONS_PASSWORD => 'legacy-generated-password',
            Config::NOTIFICATIONS_URL      => static::HTTP_URL,
        ];
        $this->locks[Config::NOTIFICATIONS_USERNAME] = true;
        $service = $this->service();
        $service->expects(self::once())->method('getSource')->with('custom-user')
            ->willReturn($this->unchangedSource($this->httpSourceName()));
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => 'https://notifications.example']);

        self::assertSame('https://notifications.example', $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame('legacy-generated-password', $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
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
        $source = $this->source($this->httpSourceName());
        $source->expects(self::once())->method('rotatePassword')->with('new-local-password');
        $source->expects(self::never())->method('create');
        $service = $this->service();
        $service->expects(self::exactly(2))->method('getSource')->with(static::CLUSTER)->willReturnOnConsecutiveCalls(
            $this->unchangedSource($this->httpSourceName()),
            $source,
        );
        $values = [Config::NOTIFICATIONS_URL => static::HTTP_URL];

        $service->save(static::CLUSTER, $values);
        $service->save(static::CLUSTER, $values, rotateHttpUser: static::CLUSTER);
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
            Config::NOTIFICATIONS_URL            => 'https://notifications.example',
            Config::NOTIFICATIONS_ICINGAWEB2_URL => 'https://example.com/icingaweb2',
        ]);

        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame('locked-transport-password', $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
        self::assertSame('https://example.com/icingaweb2', $this->getSetting(Config::NOTIFICATIONS_ICINGAWEB2_URL));
    }

    public function testNewHttpSourceUsesGeneratedPasswordAndRetainsItOnOrdinarySaves(): void
    {
        $service = $this->service();
        $service->expects(self::exactly(3))->method('getSource')->with(static::CLUSTER)
            ->willReturnOnConsecutiveCalls(
                $this->source(null),
                $this->newHttpSource(),
                $this->unchangedSource($this->httpSourceName()),
            );

        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
        $password = $this->getSetting(Config::NOTIFICATIONS_PASSWORD);
        self::assertIsString($password);
        self::assertMatchesRegularExpression('/^[a-f0-9]{40}$/D', $password);
        self::assertSame(static::CLUSTER, $this->getSetting(Config::NOTIFICATIONS_USERNAME));
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

    public function testNetworkFailureBeforeHttpSourceCreationCanBeRetriedWithSamePassword(): void
    {
        $service = $this->service();
        $service->expects(self::exactly(3))->method('getSource')->with(static::CLUSTER)->willReturnOnConsecutiveCalls(
            $this->source(null),
            $this->newHttpSource(throws: new RuntimeException('Notifications database unavailable')),
            $this->newHttpSource(),
        );

        try {
            $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
            self::fail('The source save must fail');
        } catch (RuntimeException $e) {
            self::assertSame('Notifications database unavailable', $e->getMessage());
        }

        $password = $this->getSetting(Config::NOTIFICATIONS_PASSWORD);
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_URL, $this->settings);
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_USERNAME, $this->settings);
        $service->save(static::CLUSTER, [Config::NOTIFICATIONS_URL => static::HTTP_URL]);
        self::assertSame($password, $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
    }

    public function testHttpActivationCanResumeAfterItsCommitFailsWithExplicitConfirmation(): void
    {
        $this->failCommit = 2;
        $service = $this->service();
        $service->expects(self::exactly(5))->method('getSource')->with(static::CLUSTER)->willReturnOnConsecutiveCalls(
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
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_URL, $this->settings);
        self::assertArrayNotHasKey(Config::NOTIFICATIONS_USERNAME, $this->settings);

        $unconfirmedAttempts = [
            'A Notifications source already uses' => null,
            'The confirmed HTTP source or its stored credentials are unavailable' => static::OTHER_CLUSTER,
        ];
        foreach ($unconfirmedAttempts as $message => $username) {
            try {
                $service->save(static::CLUSTER, $values, confirmedHttpUser: $username);
                self::fail('The existing source requires explicit confirmation of the correct username');
            } catch (RuntimeException $e) {
                self::assertStringContainsString($message, $e->getMessage());
                self::assertArrayNotHasKey(Config::NOTIFICATIONS_URL, $this->settings);
            }
        }

        $service->save(static::CLUSTER, $values, confirmedHttpUser: static::CLUSTER);
        self::assertSame($password, $this->getSetting(Config::NOTIFICATIONS_PASSWORD));
        self::assertSame(static::HTTP_URL, $this->getSetting(Config::NOTIFICATIONS_URL));
        self::assertSame(static::CLUSTER, $this->getSetting(Config::NOTIFICATIONS_USERNAME));
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

    protected function unchangedSource(?string $name): NotificationsSource
    {
        $source = $this->source($name);
        $source->expects(self::never())->method('create');
        $source->expects(self::never())->method('rotatePassword');

        return $source;
    }

    protected function source(?string $name): NotificationsSource&MockObject
    {
        $source = $this->createMock(NotificationsSource::class);
        $source->expects(self::once())->method('getName')->willReturn($name);

        return $source;
    }

    protected function httpSourceName(): string
    {
        return 'Icinga for Kubernetes (' . static::CLUSTER . ')';
    }

    protected function service(): NotificationsConfig&MockObject
    {
        $db = $this->createStub(Connection::class);
        $db->method('getAdapter')->willReturn(new Mysql());
        $db->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            self::assertSame('key', $identifier);

            return '`key`';
        });
        $db->method('fetchOne')->willReturnCallback(static function (string $sql, array $params): object {
            $uuid = Uuid::fromString(static::CLUSTER)->getBytes();
            self::assertSame('SELECT uuid FROM cluster WHERE uuid = ? FOR UPDATE', $sql);
            self::assertSame([$uuid], $params);

            return (object) ['uuid' => $uuid];
        });
        $db->method('fetchAll')->willReturnCallback(function (): array {
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
        $db->method('transaction')->willReturnCallback(function (callable $save) use ($db): mixed {
            $original = $this->settings;
            ++$this->transactions;
            try {
                $result = $save($db);
                if ($this->transactions === $this->failCommit) {
                    throw new RuntimeException('Simulated Kubernetes commit failure');
                }

                return $result;
            } catch (RuntimeException $e) {
                $this->settings = $original;

                throw $e;
            }
        });
        $db->method('delete')->willReturnCallback(function (string $table, array $where): PDOStatement {
            self::assertSame('config', $table);
            $this->deletes[] = $where;
            foreach ($where['`key` IN (?)'] as $key) {
                self::assertArrayNotHasKey($key, $this->locks);
                unset($this->settings[$key]);
            }

            return $this->createStub(PDOStatement::class);
        });
        $db->method('insert')->willReturnCallback(function (string $table, array $data): PDOStatement {
            self::assertSame('config', $table);
            self::assertSame('n', $data['locked']);
            $this->inserts[] = $data;
            $this->settings[$data['`key`']] = $data['value'];

            return $this->createStub(PDOStatement::class);
        });

        return $this->getMockBuilder(NotificationsConfig::class)
            ->setConstructorArgs([$db])
            ->onlyMethods(['getSource'])
            ->getMock();
    }
}

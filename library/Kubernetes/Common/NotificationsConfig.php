<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Closure;
use Icinga\Module\Kubernetes\Model\Config;
use Icinga\Module\Notifications\Common\Database as NotificationsDatabase;
use Icinga\Module\Notifications\Integrations\V1\Source;
use Icinga\Module\Notifications\Model\Source as SourceModel;
use Icinga\Module\Notifications\Repository\SourceRepository;
use ipl\I18n\Translation;
use ipl\Sql\Connection;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Cluster configuration and safely shared Notifications sources
 */
class NotificationsConfig
{
    use Translation;

    /**
     * Create a new NotificationsConfig
     *
     * @param Connection $db Kubernetes database connection
     */
    public function __construct(protected readonly Connection $db)
    {
    }

    /**
     * Save writable settings after validating local locks and the global source identity
     *
     * Unix transport removes writable HTTP credentials and reuses existing Kubernetes sources without modification.
     * Missing HTTP credentials are renewed for the cluster UUID's integration-locked source when the URL and
     * credentials are writable. Disabling a cluster keeps its source because other clusters can share it.
     *
     * @param string $clusterUuid
     * @param array<string, Config> $config Current configuration, including locally locked values
     * @param array<string, ?string> $values Submitted settings
     * @param ?string $serviceUser Fresh, unambiguous daemon process username for Unix source creation
     *
     * @return bool Whether automatic source setup is complete or unnecessary
     *
     * @throws RuntimeException If a URL, locally locked credential, or source identity conflicts
     */
    public function save(string $clusterUuid, array $config, array $values, ?string $serviceUser): bool
    {
        $values = array_filter(
            $values,
            fn(string $key): bool => ! ($config[$key]->locked ?? false),
            ARRAY_FILTER_USE_KEY,
        );
        if ($config[Config::NOTIFICATIONS_URL]->locked ?? false) {
            unset($values[Config::NOTIFICATIONS_USERNAME], $values[Config::NOTIFICATIONS_PASSWORD]);
        }

        $source = null;
        $sourceReady = true;
        if (! ($config[Config::NOTIFICATIONS_URL]->locked ?? false)) {
            $url = $values[Config::NOTIFICATIONS_URL] ?? '';
            if (! NotificationsUrl::isValid($url)) {
                throw new RuntimeException($this->translate('Invalid Icinga Notifications URL.'));
            }

            $unix = NotificationsUrl::isUnix($url);
            $usernameLocked = $config[Config::NOTIFICATIONS_USERNAME]->locked ?? false;
            $passwordLocked = $config[Config::NOTIFICATIONS_PASSWORD]->locked ?? false;
            if ($unix) {
                if (
                    ($usernameLocked && ($config[Config::NOTIFICATIONS_USERNAME]->value ?? '') !== '')
                    || ($passwordLocked && ($config[Config::NOTIFICATIONS_PASSWORD]->value ?? '') !== '')
                ) {
                    throw new RuntimeException($this->translate(
                        'Remove locally configured Notifications credentials before using a Unix socket.',
                    ));
                }

                $values[Config::NOTIFICATIONS_USERNAME] = null;
                $values[Config::NOTIFICATIONS_PASSWORD] = null;
                $sourceReady = $serviceUser !== null;
                $username = $serviceUser;
            } elseif ($url !== '') {
                $username = $usernameLocked ? $config[Config::NOTIFICATIONS_USERNAME]->value : $clusterUuid;
                if (! $usernameLocked) {
                    $values[Config::NOTIFICATIONS_USERNAME] = $username;
                }
            } else {
                $username = null;
            }

            if ($username !== null) {
                $existing = $this->findSource($username);
                if ($existing !== null && $existing->type !== Config::DEFAULT_NOTIFICATIONS_TYPE) {
                    throw new RuntimeException(sprintf($this->translate(
                        'The Notifications username "%s" belongs to another source type.'
                        . ' Configure a separate identity.',
                    ), $username));
                }

                if (! $unix || $existing === null) {
                    $password = null;
                    if (! $unix) {
                        $password = $config[Config::NOTIFICATIONS_PASSWORD]->value ?? null;
                        if ($password === null || $password === '') {
                            $canRecoverPassword = ! $usernameLocked && ! $passwordLocked
                                && $username === $clusterUuid && ($existing->locked ?? false);
                            if ($passwordLocked || ($existing !== null && ! $canRecoverPassword)) {
                                throw new RuntimeException($this->translate(
                                    'The existing Notifications source password is unavailable.'
                                    . ' Configure credentials locally.',
                                ));
                            }

                            $password = bin2hex(random_bytes(20));
                            $values[Config::NOTIFICATIONS_PASSWORD] = $password;
                        }
                    }

                    $source = $this->getSource($username, $existing);
                    if ($existing === null) {
                        $source->setName(
                            $unix ? Config::DEFAULT_NOTIFICATIONS_NAME : Config::DEFAULT_NOTIFICATIONS_NAME
                                . " ($clusterUuid)",
                        );
                    }

                    $source->setType(Config::DEFAULT_NOTIFICATIONS_TYPE);
                    if ($password !== null) {
                        $source->setPassword($password);
                    }
                }
            }
        }

        $this->db->transaction(function (Connection $db) use ($values, $clusterUuid, $source): void {
            $key = $db->quoteIdentifier('key');
            $db->delete((new Config())->getTableName(), [
                'cluster_uuid = ?'         => Uuid::fromString($clusterUuid)->getBytes(),
                sprintf('%s IN (?)', $key) => array_keys($values),
                'locked = ?'               => 'n',
            ]);

            foreach ($values as $name => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $db->insert((new Config())->getTableName(), [
                    'cluster_uuid' => Uuid::fromString($clusterUuid)->getBytes(),
                    $key           => $name,
                    'value'        => $value,
                ]);
            }

            $source?->save();
        });

        return $sourceReady;
    }

    /**
     * Get the existing global source identity before changing either database
     *
     * @param string $username
     *
     * @return ?SourceModel
     */
    protected function findSource(string $username): ?SourceModel
    {
        return $this->getSourceRepository()->findByUsername($username);
    }

    /**
     * Get an integration source from the validated identity snapshot
     *
     * A new identity remains an insert even if another source claims its username concurrently.
     *
     * @param string $username
     * @param ?SourceModel $existing The source whose type has already been validated
     *
     * @return Source
     */
    protected function getSource(string $username, ?SourceModel $existing): Source
    {
        return new Source(
            $this->getSourceTransaction(),
            $this->getSourceRepository(),
            $username,
            $existing->id ?? null,
            $existing->type ?? null,
            $existing->name ?? null,
        );
    }

    /**
     * Get the Notifications source repository
     *
     * @return SourceRepository
     */
    protected function getSourceRepository(): SourceRepository
    {
        return new SourceRepository(NotificationsDatabase::get());
    }

    /**
     * Get the transaction wrapper for Notifications source persistence
     *
     * @return Closure(callable): mixed
     */
    protected function getSourceTransaction(): Closure
    {
        return NotificationsDatabase::get()->transaction(...);
    }
}

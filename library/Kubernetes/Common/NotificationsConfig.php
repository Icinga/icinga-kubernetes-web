<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Model\Config;
use Icinga\Module\Notifications\Integrations\V1\Source;
use ipl\I18n\Translation;
use ipl\Sql\Connection;
use RuntimeException;

/**
 * Cluster configuration and Notifications source provisioning
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
     * Save writable settings, rechecking cluster settings and discovery before provisioning a source
     *
     * A new HTTP password is committed before provisioning, while the username and URL are activated together.
     * The Notifications API commits separately. If an insert succeeded before activation failed, only an
     * administrator can confirm reuse of the existing source.
     *
     * @param string $clusterUuid
     * @param array<string, ?string> $values Submitted URL settings
     * @param ?string $discoveryConfirmation Confirmation for assisted Unix setup, or null for manual setup
     * @param ?string $confirmedHttpUser Username explicitly confirmed for reuse, or null for automatic HTTP setup
     * @param ?string $rotateHttpUser Username explicitly confirmed for password rotation, or null for an ordinary save
     *
     * @return void
     *
     * @throws RuntimeException If submitted settings, the current locks, or the source identity conflict
     */
    public function save(
        string $clusterUuid,
        array $values,
        ?string $discoveryConfirmation = null,
        ?string $confirmedHttpUser = null,
        ?string $rotateHttpUser = null,
    ): void {
        $save = fn(bool $stagePassword): bool => $this->saveSettings(
            $clusterUuid,
            $values,
            $discoveryConfirmation,
            $confirmedHttpUser,
            $rotateHttpUser,
            $stagePassword,
        );

        if (! $this->db->transaction(fn(): bool => $save(true))) {
            $this->db->transaction(fn(): bool => $save(false));
        }
    }

    /**
     * Save the settings under a cluster row lock, or commit a new HTTP password for the next transaction
     *
     * @param string $clusterUuid
     * @param array<string, ?string> $values
     * @param ?string $discoveryConfirmation
     * @param ?string $confirmedHttpUser
     * @param ?string $rotateHttpUser
     * @param bool $stagePassword Whether to allow creating an inactive password
     *
     * @return bool Whether the settings have been saved
     *
     * @throws RuntimeException If the cluster disappeared or the settings conflict
     */
    protected function saveSettings(
        string $clusterUuid,
        array $values,
        ?string $discoveryConfirmation,
        ?string $confirmedHttpUser,
        ?string $rotateHttpUser,
        bool $stagePassword,
    ): bool {
        $uuid = NotificationsDiscovery::encodeClusterUuid($this->db, $clusterUuid);
        if ($this->db->fetchOne('SELECT uuid FROM cluster WHERE uuid = ? FOR UPDATE', [$uuid]) === false) {
            throw new RuntimeException($this->translate('The selected cluster is no longer available.'));
        }

        $key = $this->db->quoteIdentifier('key');
        $table = (new Config())->getTableName();
        $settings = [];
        $rows = $this->db->fetchAll(
            "SELECT $key, value, locked FROM $table WHERE $key IN (?, ?, ?, ?) AND cluster_uuid = ? FOR UPDATE",
            [
                Config::NOTIFICATIONS_URL,
                Config::NOTIFICATIONS_USERNAME,
                Config::NOTIFICATIONS_PASSWORD,
                Config::NOTIFICATIONS_ICINGAWEB2_URL,
                $uuid,
            ],
        );
        foreach ($rows as $row) {
            $settings[$row->key] = $row;
        }

        $urlLocked = ($settings[Config::NOTIFICATIONS_URL]->locked ?? 'n') === 'y';
        $updates = [];
        if (($settings[Config::NOTIFICATIONS_ICINGAWEB2_URL]->locked ?? 'n') !== 'y') {
            $updates[Config::NOTIFICATIONS_ICINGAWEB2_URL] = $values[Config::NOTIFICATIONS_ICINGAWEB2_URL] ?? null;
        }

        $url = $values[Config::NOTIFICATIONS_URL] ?? '';
        if ($rotateHttpUser !== null && ($urlLocked || ! NotificationsUrl::isWeb($url))) {
            throw new RuntimeException($this->translate(
                'Password rotation requires a writable HTTP or HTTPS Notifications URL.'
            ));
        }

        $serviceUser = null;
        if ($discoveryConfirmation !== null) {
            $discovery = NotificationsDiscovery::load($this->db, $clusterUuid);
            $serviceUser = $discovery->serviceUser;
            if (
                $urlLocked
                || $serviceUser === null
                || ! $discovery->matchesConfirmation($clusterUuid, $url, $discoveryConfirmation)
            ) {
                throw new RuntimeException($this->translate(
                    'The discovered socket or process user changed, or the URL was locked. Confirm the settings again.'
                ));
            }
        }

        $source = null;
        $sourceName = Config::DEFAULT_NOTIFICATIONS_NAME;
        $sourcePassword = null;
        if (! $urlLocked) {
            if (! NotificationsUrl::isValid($url)) {
                throw new RuntimeException($this->translate('Invalid Icinga Notifications URL.'));
            }

            $updates[Config::NOTIFICATIONS_URL] = $url;
            $username = $settings[Config::NOTIFICATIONS_USERNAME]->value ?? '';
            $password = $settings[Config::NOTIFICATIONS_PASSWORD]->value ?? '';
            $usernameLocked = ($settings[Config::NOTIFICATIONS_USERNAME]->locked ?? 'n') === 'y';
            $passwordLocked = ($settings[Config::NOTIFICATIONS_PASSWORD]->locked ?? 'n') === 'y';

            if (NotificationsUrl::isUnix($url)) {
                if (($usernameLocked && $username !== '') || ($passwordLocked && $password !== '')) {
                    throw new RuntimeException($this->translate(
                        'Remove locally configured Notifications credentials before using a Unix socket.'
                    ));
                }

                if ($serviceUser !== null) {
                    $source = $this->getSource($serviceUser);
                    if ($source->getName() !== null) {
                        throw new RuntimeException(sprintf($this->translate(
                            'A Notifications source already uses "%s". Verify that it is a Kubernetes source,'
                            . ' then clear the discovery checkbox and save the socket URL manually.'
                        ), $serviceUser));
                    }
                }
            } elseif ($url !== '') {
                $sourceName .= " ($clusterUuid)";
                $httpActive = NotificationsUrl::isWeb($settings[Config::NOTIFICATIONS_URL]->value ?? '');
                $source = $this->prepareHttpSource(
                    $clusterUuid,
                    $username,
                    $password,
                    $usernameLocked,
                    $passwordLocked,
                    $httpActive,
                    $sourceName,
                    $confirmedHttpUser,
                    $rotateHttpUser,
                );

                if ($password === '') {
                    if ($usernameLocked && $httpActive) {
                        throw new RuntimeException($this->translate(
                            'Disable Notifications for the cluster before regenerating its missing HTTP password.'
                        ));
                    }

                    if (! $stagePassword) {
                        throw new RuntimeException($this->translate(
                            'The Notifications credentials changed. Reload the form and try again.'
                        ));
                    }

                    $credentials = [
                        Config::NOTIFICATIONS_PASSWORD => bin2hex(random_bytes(20)),
                    ];
                    if (! $usernameLocked) {
                        // Without a username, an unactivated password cannot be used to adopt an existing source.
                        $credentials[Config::NOTIFICATIONS_USERNAME] = null;
                    }

                    $this->writeSettings($uuid, $credentials);

                    return false;
                }

                if (! $usernameLocked) {
                    $updates[Config::NOTIFICATIONS_USERNAME] = $clusterUuid;
                }
                $sourcePassword = $password;
            }
        }

        $this->writeSettings($uuid, $updates);

        if ($rotateHttpUser !== null && $source !== null && $sourcePassword !== null) {
            $source->rotatePassword($sourcePassword);
        } else {
            // Retain the handle that was checked for vacancy. A concurrent claim fails the insert.
            $source?->create($sourceName, $sourcePassword);
        }

        return true;
    }

    /**
     * Prepare a new HTTP source or validate the use of existing credentials without changing the source
     *
     * @param string $clusterUuid
     * @param string $storedUser
     * @param string $password
     * @param bool $usernameLocked
     * @param bool $passwordLocked
     * @param bool $httpActive Whether an HTTP URL has already been activated
     * @param string $name Default source name for the cluster
     * @param ?string $confirmedUser
     * @param ?string $rotateUser
     *
     * @return ?NotificationsSource A new source or the existing source confirmed for password rotation
     *
     * @throws RuntimeException If credentials or ownership cannot be established
     */
    protected function prepareHttpSource(
        string $clusterUuid,
        string $storedUser,
        string $password,
        bool $usernameLocked,
        bool $passwordLocked,
        bool $httpActive,
        string $name,
        ?string $confirmedUser,
        ?string $rotateUser,
    ): ?NotificationsSource {
        if (($usernameLocked && $storedUser === '') || ($passwordLocked && $password === '')) {
            throw new RuntimeException($this->translate(
                'Configure a nonempty username and password for the Notifications credentials set in the daemon.'
            ));
        }

        $username = $usernameLocked ? $storedUser : $clusterUuid;
        $source = $this->getSource($username);
        $existingName = $source->getName();
        if ($rotateUser !== null) {
            if (
                $confirmedUser !== null
                || ! $httpActive
                || ! $passwordLocked
                || $storedUser !== $username
                || $rotateUser !== $username
                || $existingName !== $name
            ) {
                throw new RuntimeException($this->translate(
                    'Password rotation requires the dedicated Kubernetes source for this cluster and a locally'
                    . ' configured password. For a shared source, coordinate changes with its administrator.'
                ));
            }

            return $source;
        }

        if ($confirmedUser !== null && ($confirmedUser !== $username || $existingName === null || $password === '')) {
            throw new RuntimeException($this->translate(
                'The confirmed HTTP source or its stored credentials are unavailable. Reload the form and try again.'
            ));
        }

        if ($existingName !== null) {
            $established = $storedUser === $username && $password !== '' && $existingName === $name
                && (! $usernameLocked || $httpActive);
            if (
                $confirmedUser !== null
                || ($usernameLocked && $passwordLocked)
                || $established
            ) {
                return null;
            }

            throw new RuntimeException(sprintf($this->translate(
                'A Notifications source already uses "%s". Verify the source, then select "Use an existing HTTP'
                . ' source" to keep this cluster\'s stored password. To use different credentials, configure'
                . ' them in the daemon configuration.'
            ), $username));
        }

        return $source;
    }

    /**
     * Replace writable cluster settings in the current transaction
     *
     * @param string $uuid Database representation of the cluster UUID
     * @param array<string, ?string> $updates Empty or null values remove a setting
     *
     * @return void
     */
    protected function writeSettings(string $uuid, array $updates): void
    {
        if ($updates === []) {
            return;
        }

        $key = $this->db->quoteIdentifier('key');
        $table = (new Config())->getTableName();
        $this->db->delete($table, [
            'cluster_uuid = ?'          => $uuid,
            sprintf('%s IN (?)', $key) => array_keys($updates),
            'locked = ?'               => 'n',
        ]);
        foreach ($updates as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $this->db->insert($table, [
                'cluster_uuid' => $uuid,
                $key           => $name,
                'value'        => $value,
                'locked'       => 'n',
            ]);
        }
    }

    /**
     * Get the public API handle for a source username
     *
     * @param string $username
     *
     * @return NotificationsSource
     *
     * @throws RuntimeException If Notifications Web does not provide the V1 API
     */
    protected function getSource(string $username): NotificationsSource
    {
        if (! class_exists(Source::class)) {
            throw new RuntimeException($this->translate(
                'Automatic source setup requires Icinga Notifications Web 1.0 or later.'
            ));
        }

        return NotificationsSource::get($username);
    }
}

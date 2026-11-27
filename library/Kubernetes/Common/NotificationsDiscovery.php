<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Model\Cluster;
use ipl\Sql\Connection;
use stdClass;

/**
 * Unambiguous Notifications metadata from live instances of one cluster
 */
readonly class NotificationsDiscovery
{
    /**
     * Create a new NotificationsDiscovery
     *
     * @param ?string $socketPath An absolute socket path agreed on by all live instances
     * @param ?string $serviceUser A process username agreed on by all live instances
     */
    public function __construct(public ?string $socketPath, public ?string $serviceUser)
    {
    }

    /**
     * Get fresh discovery evidence for the selected cluster
     *
     * Missing metadata in an older daemon schema leaves assistance unavailable.
     *
     * @param Connection $db
     * @param string $clusterUuid
     *
     * @return self
     */
    public static function load(Connection $db, string $clusterUuid): self
    {
        // Selecting existing columns also supports schemas without discovery metadata.
        return static::fromRows($db->fetchAll(
            'SELECT * FROM kubernetes_instance WHERE cluster_uuid = ?',
            [Cluster::encodeUuid($db, $clusterUuid)],
        ), time());
    }

    /**
     * Get the metadata shared by live instances
     *
     * A missing or differing value makes that part of discovery unavailable.
     * Heartbeats have millisecond precision. The 60-second cutoff matches the health check.
     *
     * @param iterable<stdClass> $rows Rows belonging to the selected cluster
     * @param int $now Current Unix timestamp in seconds
     *
     * @return self
     */
    public static function fromRows(iterable $rows, int $now): self
    {
        $path = null;
        $user = null;
        $first = true;
        foreach ($rows as $row) {
            if ($row->heartbeat < ($now - 60) * 1000) {
                continue;
            }

            $reportedPath = $row->notifications_discovered_socket_path ?? null;
            $reportedUser = $row->kubernetes_service_user ?? null;
            if ($first) {
                $path = $reportedPath;
                $user = $reportedUser;
                $first = false;

                continue;
            }

            if ($reportedPath !== $path) {
                $path = null;
            }
            if ($reportedUser !== $user) {
                $user = null;
            }
        }

        return new self(
            $path !== null && NotificationsUrl::isUnix(NotificationsUrl::fromSocketPath($path)) ? $path : null,
            $user !== null && $user !== '' ? $user : null,
        );
    }

    /**
     * Get a confirmation bound to the selected cluster and the discovery evidence shown to the user
     *
     * @param string $clusterUuid
     *
     * @return ?string Null when the socket URL cannot be saved or the process user is unavailable
     */
    public function getConfirmation(string $clusterUuid): ?string
    {
        if (
            $this->socketPath === null
            || $this->serviceUser === null
            || ! NotificationsUrl::isValid(NotificationsUrl::fromSocketPath($this->socketPath))
        ) {
            return null;
        }

        return hash('sha256', $clusterUuid . "\0" . $this->socketPath . "\0" . $this->serviceUser);
    }

    /**
     * Check whether a submission matches the confirmed socket URL and process user
     *
     * @param string $clusterUuid
     * @param string $url
     * @param string $confirmation
     *
     * @return bool
     */
    public function matchesConfirmation(string $clusterUuid, string $url, string $confirmation): bool
    {
        return $this->socketPath !== null
            && $this->getConfirmation($clusterUuid) === $confirmation
            && NotificationsUrl::fromSocketPath($this->socketPath) === $url;
    }
}

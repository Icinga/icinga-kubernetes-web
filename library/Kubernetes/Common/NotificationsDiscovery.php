<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use Ramsey\Uuid\Uuid;
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
        $uuid = Uuid::fromString($clusterUuid)->getBytes();
        if ($db->getAdapter() instanceof Pgsql) {
            $uuid = '\\x' . bin2hex($uuid);
        }

        // Selecting existing columns also supports schemas without discovery metadata.
        return static::fromRows($db->fetchAll(
            'SELECT * FROM kubernetes_instance WHERE cluster_uuid = ?',
            [$uuid],
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
        $paths = [];
        $users = [];
        foreach ($rows as $row) {
            if ($row->heartbeat < ($now - 60) * 1000) {
                continue;
            }

            $paths[] = $row->notifications_discovered_socket_path ?? null;
            $users[] = $row->kubernetes_service_user ?? null;
        }

        $paths = array_unique($paths, SORT_REGULAR);
        $users = array_unique($users, SORT_REGULAR);
        $path = count($paths) === 1 ? reset($paths) : null;
        $user = count($users) === 1 ? reset($users) : null;

        return new self(
            $path !== null && NotificationsUrl::isUnix(NotificationsUrl::fromSocketPath($path)) ? $path : null,
            $user !== null && $user !== '' ? $user : null,
        );
    }
}

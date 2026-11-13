<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Model\Cluster;
use Ramsey\Uuid\Uuid;

/**
 * Provide the known clusters as options for cluster select elements
 */
trait ClusterOptions
{
    /** @var string Option value for all clusters */
    public const ALL_CLUSTERS = '-';

    /**
     * Fetch all clusters as UUID-label pairs
     *
     * @return array<string, string>
     */
    public static function fetchClusters(): array
    {
        $clusters = Cluster::on(Database::connection())
            ->columns(['uuid', 'name']);

        $options = [];
        foreach ($clusters as $cluster) {
            $uuid = (string) Uuid::fromBytes($cluster->uuid);
            $options[$uuid] = $cluster->name ?? $uuid;
        }

        return $options;
    }
}

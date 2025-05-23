<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

use Generator;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Cluster;
use ipl\Web\Compat\CompatForm;
use Ramsey\Uuid\Uuid;

class ClusterForm extends CompatForm
{
    public const ALL_CLUSTERS = '-';

    public static function yieldClusters(): Generator
    {
        $clusters = Cluster::on(Database::connection())
            ->columns(['uuid', 'name']);

        foreach ($clusters as $cluster) {
            $label = $cluster->name ?? (string) Uuid::fromBytes($cluster->uuid);

            yield (string) Uuid::fromBytes($cluster->uuid) => $label;
        }
    }

    protected function assemble(): void
    {
        $this->addElement(
            'select',
            'cluster_uuid',
            [
                'required' => true,
                'class'    => 'autosubmit',
                'label'    => $this->translate('Cluster'),
                'options'  => [
                        static::ALL_CLUSTERS => $this->translate('All clusters'),
                    ] + iterator_to_array(ClusterForm::yieldClusters()),
            ],
        );
    }
}

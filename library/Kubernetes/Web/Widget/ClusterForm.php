<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

use Icinga\Module\Kubernetes\Common\ClusterOptions;
use ipl\Web\Compat\CompatForm;

class ClusterForm extends CompatForm
{
    use ClusterOptions;

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
                    ] + static::fetchClusters(),
            ],
        );
    }
}

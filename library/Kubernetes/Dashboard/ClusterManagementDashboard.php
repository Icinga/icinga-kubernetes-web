<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class ClusterManagementDashboard extends Dashboard
{
    protected $dashletNames = [
        'Namespace',
        'Node'
    ];

    public function getTitle()
    {
        return $this->translate('Cluster management');
    }
}

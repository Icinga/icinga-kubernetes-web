<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class WorkloadsDashboard extends Dashboard
{
    protected $dashletNames = [
        'Cronjob',
        'DaemonSet',
        'Deployment',
        'Job',
        'Pod',
        'ReplicaSet',
        'StatefulSet'
    ];

    public function getTitle()
    {
        return $this->translate('Workloads');
    }
}

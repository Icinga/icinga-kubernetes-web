<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class AdditionalDashboard extends Dashboard
{
    protected $dashletNames = [
        'ClusterService'
    ];

    public function getTitle()
    {
        return $this->translate('Additional Categories');
    }
}

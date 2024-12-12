<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class ConfigurationDashboard extends Dashboard
{
    protected $dashletNames = [
        'ConfigMap',
        'Secret'
    ];

    public function getTitle()
    {
        return $this->translate('Configuration');
    }
}

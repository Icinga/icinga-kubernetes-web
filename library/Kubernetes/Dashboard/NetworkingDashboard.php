<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class NetworkingDashboard extends Dashboard
{
    protected $dashletNames = [
        'Service',
        'Ingress'
    ];

    public function getTitle()
    {
        return $this->translate('Networking');
    }
}

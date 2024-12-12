<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Dashboard\Dashboard;
use Icinga\Module\Kubernetes\Web\Controller;
use ipl\Web\Compat\CompatForm;

class DashboardController extends Controller
{
    public function indexAction()
    {
        $mainDashboards = [
            'Workloads',
            'Networking',
            'Storage',
            'Configuration',
            'ClusterManagement',
            'Observability',
            'Additional'
        ];

        $this->addTitleTab($this->translate('Kubernetes'));

        $names = $this->params->getValues('name', $mainDashboards);

        foreach ($names as $name) {
            $dashboard = Dashboard::loadByName($name);
            $this->addContent($dashboard);
        }
    }
}

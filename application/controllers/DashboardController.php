<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Dashboard\ClusterManagementDashboard;
use Icinga\Module\Kubernetes\Dashboard\ConfigurationDashboard;
use Icinga\Module\Kubernetes\Dashboard\NetworkingDashboard;
use Icinga\Module\Kubernetes\Dashboard\ObservabilityDashboard;
use Icinga\Module\Kubernetes\Dashboard\StorageDashboard;
use Icinga\Module\Kubernetes\Dashboard\WorkloadsDashboard;
use Icinga\Module\Kubernetes\Web\Controller;

class DashboardController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Kubernetes'));

        $this->content->addHtml(
            new ClusterManagementDashboard(),
            new WorkloadsDashboard(),
            new StorageDashboard(),
            new NetworkingDashboard(),
            new ConfigurationDashboard(),
            new ObservabilityDashboard(),
        );
    }
}

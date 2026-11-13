<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Dashboard\ClusterManagementDashboard;
use Icinga\Module\Kubernetes\Dashboard\ConfigurationDashboard;
use Icinga\Module\Kubernetes\Dashboard\NetworkingDashboard;
use Icinga\Module\Kubernetes\Dashboard\ObservabilityDashboard;
use Icinga\Module\Kubernetes\Dashboard\StorageDashboard;
use Icinga\Module\Kubernetes\Dashboard\WorkloadsDashboard;
use Icinga\Module\Kubernetes\Web\Controller\Controller;

class DashboardController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Kubernetes'));
        $this->controls->addAttributes(['class' => ['no-shadow', 'kubernetes-dashboard-controls']]);

        $clusterSwitcher = $this->createClusterSwitcher();
        if ($clusterSwitcher->hasMultipleClusters()) {
            $this->addControl($clusterSwitcher);
        }

        $clusterUuid = $clusterSwitcher->getClusterUuid();
        $this->content->addHtml(
            new ClusterManagementDashboard($clusterUuid),
            new WorkloadsDashboard($clusterUuid),
            new StorageDashboard($clusterUuid),
            new NetworkingDashboard($clusterUuid),
            new ConfigurationDashboard($clusterUuid),
            new ObservabilityDashboard($clusterUuid),
        );
    }
}

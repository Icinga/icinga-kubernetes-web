<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Dashboard\ClusterManagementDashboard;
use Icinga\Module\Kubernetes\Dashboard\ConfigurationDashboard;
use Icinga\Module\Kubernetes\Dashboard\NetworkingDashboard;
use Icinga\Module\Kubernetes\Dashboard\ObservabilityDashboard;
use Icinga\Module\Kubernetes\Dashboard\StorageDashboard;
use Icinga\Module\Kubernetes\Dashboard\WorkloadsDashboard;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\Web\ClusterForm;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Web\Session;

class DashboardController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Kubernetes'));

        $cluster = (new ClusterForm())
            ->populate(
                [
                    'cluster_uuid' => Session::getSession()
                        ->getNamespace('kubernetes')
                        ->get('cluster_uuid', ClusterForm::ALL_CLUSTERS)
                ]
            )
            ->on(ClusterForm::ON_SUCCESS, function (ClusterForm $form) {
                $session = Session::getSession()
                    ->getNamespace('kubernetes');
                $clusterUuid = $form->getElement('cluster_uuid')->getValue();
                if ($clusterUuid === ClusterForm::ALL_CLUSTERS) {
                    $session->set('cluster_uuid', null);
                } else {
                    $session->set('cluster_uuid', $clusterUuid);
                }
            })
            ->handleRequest(ServerRequest::fromGlobals());

        if ($this->isMultiCluster()) {
            $this->addContent($cluster);
        }

        $this->content->addHtml(
            new ClusterManagementDashboard(),
            new WorkloadsDashboard(),
            new StorageDashboard(),
            new NetworkingDashboard(),
            new ConfigurationDashboard(),
            new ObservabilityDashboard(),
        );
    }

    protected function isMultiCluster(): bool
    {
        return Cluster::on(Database::connection())->count() > 1;
    }
}

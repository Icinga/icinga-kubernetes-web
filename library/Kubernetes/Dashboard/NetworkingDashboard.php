<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class NetworkingDashboard extends Dashboard
{
    protected function getTitle(): string
    {
        return $this->translate('Networking');
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Dashlet(
                'service',
                $this->translate('Services'),
                $this->translate(
                    'Expose Pods within or outside the cluster. Types: ClusterIP, NodePort, LoadBalancer,
                    ExternalName.'
                )
            ),
            new Dashlet(
                'service',
                $this->translate('Cluster Services'),
                $this->translate(
                    "Core components that manage and support the Kubernetes control plane, networking, and
                    storage, ensuring the cluster's overall health and operation."
                ),
                'kubernetes/services?label.name=kubernetes.io%2Fcluster-service&label.value=true',
            ),
            new Dashlet(
                'ingress',
                $this->translate('Ingresses'),
                $this->translate('Manage HTTP/HTTPS traffic into the cluster.')
            ),
        );
    }
}

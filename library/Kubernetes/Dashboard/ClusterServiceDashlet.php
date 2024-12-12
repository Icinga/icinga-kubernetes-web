<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use ipl\I18n\Translation;

class ClusterServiceDashlet extends Dashlet
{
    use Translation;

    protected $icon = 'icon kicon-service';

    public function getTitle()
    {
        return $this->translate('Cluster Services');
    }

    public function getSummary()
    {
        return $this->translate(
            "Core components that manage and support the Kubernetes control plane, networking, and storage, 
            ensuring the cluster's overall health and operation"
        );
    }

    public function getUrl()
    {
        return 'kubernetes/services?label.name=kubernetes.io%2Fcluster-service&label.value=true';
    }
}

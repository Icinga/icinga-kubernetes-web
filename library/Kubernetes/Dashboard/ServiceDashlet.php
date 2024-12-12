<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use ipl\I18n\Translation;

class ServiceDashlet extends Dashlet
{
    use Translation;

    protected $icon = 'icon kicon-service';

    public function getTitle()
    {
        return $this->translate('Services');
    }

    public function getSummary()
    {
        return $this->translate(
            'Expose Pods within or outside the cluster. Types: ClusterIP, NodePort, LoadBalancer, ExternalName'
        );
    }

    public function getUrl()
    {
        return 'kubernetes/services';
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use ipl\I18n\Translation;

class IngressDashlet extends Dashlet
{
    use Translation;

    protected $icon = 'icon kicon-ingress';

    public function getTitle()
    {
        return $this->translate('Ingresses');
    }

    public function getSummary()
    {
        return $this->translate('Manage HTTP/HTTPS traffic into the cluster');
    }

    public function getUrl()
    {
        return 'kubernetes/ingresses';
    }
}

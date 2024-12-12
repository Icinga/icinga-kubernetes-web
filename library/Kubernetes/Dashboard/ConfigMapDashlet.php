<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use ipl\I18n\Translation;

class ConfigMapDashlet extends Dashlet
{
    use Translation;

    protected $icon = 'icon kicon-config-map';

    public function getTitle()
    {
        return $this->translate('Config Maps');
    }

    public function getSummary()
    {
        return $this->translate('Store configuration data as key-value pairs');
    }

    public function getUrl()
    {
        return 'kubernetes/config-maps';
    }
}

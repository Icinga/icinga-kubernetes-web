<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use ipl\I18n\Translation;

class EventDashlet extends Dashlet
{
    use Translation;

    protected $icon = 'icon kicon-event';

    public function getTitle()
    {
        return $this->translate('Events');
    }

    public function getSummary()
    {
        return $this->translate('Record changes and issues within the cluster');
    }

    public function getUrl()
    {
        return 'kubernetes/events';
    }
}

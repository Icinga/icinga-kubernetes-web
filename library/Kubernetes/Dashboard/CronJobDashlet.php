<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use ipl\I18n\Translation;

class CronJobDashlet extends Dashlet
{
    use Translation;

    protected $icon = 'icon kicon-cron-job';

    public function getTitle()
    {
        return $this->translate('Cron Jobs');
    }

    public function getSummary()
    {
        return $this->translate('Schedule Jobs to run at specific times');
    }

    public function getUrl()
    {
        return 'kubernetes/cron-jobs';
    }
}

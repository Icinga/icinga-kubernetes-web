<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Job;
use ipl\Stdlib\Filter;

class JobDashlet extends Dashlet
{
    protected $icon = 'icon kicon-job';

    public function getTitle()
    {
        return $this->translate('Jobs');
    }

    public function getSummary()
    {
        return $this->translate(
            sprintf(
                'Out of %s total jobs, %s are in OK state, %s are Pending, %s are Critical, %s are in Warning 
                state, and %s are Unknown',
                $this->getIcingaStateCount(),
                $this->getIcingaStateCount('ok'),
                $this->getIcingaStateCount('pending'),
                $this->getIcingaStateCount('critical'),
                $this->getIcingaStateCount('warning'),
                $this->getIcingaStateCount('unknown')
            )
        );
    }

    private function getIcingaStateCount(string $icingaState = '')
    {
        return $icingaState === ''
            ? Job::on(Database::connection())->count()
            : Job::on(Database::connection())->filter(Filter::equal('icinga_state', $icingaState))->count();
    }

    public function getUrl()
    {
        return 'kubernetes/jobs';
    }
}

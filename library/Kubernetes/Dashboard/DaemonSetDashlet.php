<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use ipl\Stdlib\Filter;

class DaemonSetDashlet extends Dashlet
{
    protected $icon = 'icon kicon-daemon-set';

    public function getTitle()
    {
        return $this->translate('Daemon Sets');
    }

    public function getSummary()
    {
        return $this->translate(
            sprintf(
                'Out of %s total daemon sets, %s are in OK state, %s are Critical, %s are in Warning state, and 
                %s are Unknown',
                $this->getIcingaStateCount(),
                $this->getIcingaStateCount('ok'),
                $this->getIcingaStateCount('critical'),
                $this->getIcingaStateCount('warning'),
                $this->getIcingaStateCount('unknown')
            )
        );
    }

    private function getIcingaStateCount(string $icingaState = '')
    {
        return $icingaState === ''
            ? DaemonSet::on(Database::connection())->count()
            : DaemonSet::on(Database::connection())->filter(Filter::equal('icinga_state', $icingaState))->count();
    }

    public function getUrl()
    {
        return 'kubernetes/daemon-sets';
    }
}

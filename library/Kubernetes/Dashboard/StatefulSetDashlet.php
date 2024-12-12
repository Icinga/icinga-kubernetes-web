<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use ipl\Stdlib\Filter;

class StatefulSetDashlet extends Dashlet
{
    protected $icon = 'icon kicon-stateful-set';

    public function getTitle()
    {
        return $this->translate('Stateful Sets');
    }

    public function getSummary()
    {
        return $this->translate(
            sprintf(
                'Out of %s total stateful sets, %s are in OK state, %s are Critical, %s are in Warning state,
                and %s are Unknown',
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
            ? StatefulSet::on(Database::connection())->count()
            : StatefulSet::on(Database::connection())->filter(Filter::equal('icinga_state', $icingaState))->count();
    }

    public function getUrl()
    {
        return 'kubernetes/stateful-sets';
    }
}

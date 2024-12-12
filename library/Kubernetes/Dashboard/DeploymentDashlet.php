<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Deployment;
use ipl\Stdlib\Filter;

class DeploymentDashlet extends Dashlet
{
    protected $icon = 'icon kicon-deployment';

    public function getTitle()
    {
        return $this->translate('Deployments');
    }

    public function getSummary()
    {
        return $this->translate(
            sprintf(
                'Out of %s total deployments, %s are in OK state, %s are Critical, %s are in Warning state, and 
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
            ? Deployment::on(Database::connection())->count()
            : Deployment::on(Database::connection())->filter(Filter::equal('icinga_state', $icingaState))->count();
    }

    public function getUrl()
    {
        return 'kubernetes/deployments';
    }
}

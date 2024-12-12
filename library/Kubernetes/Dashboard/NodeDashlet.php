<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Node;
use ipl\Stdlib\Filter;

class NodeDashlet extends Dashlet
{
    protected $icon = 'icon fa fa-share-nodes';

    public function getTitle()
    {
        return $this->translate('Nodes');
    }

    public function getSummary()
    {
        return $this->translate(
            sprintf(
                'Out of %s total nodes, %s are in OK state, %s are Critical, %s are in Warning state, and %s are
                Unknown',
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
            ? Node::on(Database::connection())->count()
            : Node::on(Database::connection())->filter(Filter::equal('icinga_state', $icingaState))->count();
    }

    public function getUrl()
    {
        return 'kubernetes/nodes';
    }
}

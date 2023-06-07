<?php

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\ReplicaSetDetail;
use ipl\Stdlib\Filter;

class ReplicasetController extends Controller
{
    public function indexAction(): void
    {
        $namespace = $this->params->getRequired('namespace');
        $name = $this->params->getRequired('name');
        $this->addTitleTab("Replica Set $namespace/$name");

        $replicaSet = ReplicaSet::on(Database::connection())
            ->filter(Filter::equal('namespace', $namespace))
            ->filter(Filter::equal('name', $name))
            ->first();

        $this->addContent(new ReplicaSetDetail($replicaSet));
    }

    protected function getPageSize($default)
    {
        return parent::getPageSize($default ?? 50);
    }
}

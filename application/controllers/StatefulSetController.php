<?php

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\StatefulSetDetail;
use ipl\Stdlib\Filter;

class StatefulSetController extends Controller
{
    public function indexAction(): void
    {
        $namespace = $this->params->getRequired('namespace');
        $name = $this->params->getRequired('name');
        $this->addTitleTab("Stateful Set $namespace/$name");

        $statefulSet = StatefulSet::on(Database::connection())
            ->filter(Filter::equal('namespace', $namespace))
            ->filter(Filter::equal('name', $name))
            ->first();

        $this->addContent(new StatefulSetDetail($statefulSet));
    }

    protected function getPageSize($default)
    {
        return parent::getPageSize($default ?? 50);
    }
}

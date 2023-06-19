<?php

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\StatefulSetDetail;
use ipl\Stdlib\Filter;

class StatefulsetController extends Controller
{
    public function indexAction(): void
    {
        $namespace = $this->params->get('namespace');
        $name = $this->params->get('name');
        $id = $this->params->getRequired('id');

        $this->addTitleTab("Stateful Set $namespace/$name");

        $statefulSet = StatefulSet::on(Database::connection())
            ->filter(Filter::equal('id', $id))
            ->first();

        $this->addContent(new StatefulSetDetail($statefulSet));
    }

    protected function getPageSize($default)
    {
        return parent::getPageSize($default ?? 50);
    }
}

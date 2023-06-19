<?php

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\DaemonSetDetail;
use ipl\Stdlib\Filter;

class DaemonsetController extends Controller
{
    public function indexAction(): void
    {
        $namespace = $this->params->getRequired('namespace');
        $name = $this->params->getRequired('name');
        $this->addTitleTab("Daemon Set $namespace/$name");

        $daemonSet = DaemonSet::on(Database::connection())
            ->filter(Filter::equal('namespace', $namespace))
            ->filter(Filter::equal('name', $name))
            ->first();

        $this->addContent(new DaemonSetDetail($daemonSet));
    }

    protected function getPageSize($default)
    {
        return parent::getPageSize($default ?? 50);
    }
}

<?php

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\DeploymentDetail;
use ipl\Stdlib\Filter;

class DeploymentController extends Controller
{
    public function indexAction(): void
    {
        $namespace = $this->params->get('namespace');
        $name = $this->params->get('name');
        $id = $this->params->getRequired('id');

        $this->addTitleTab("Deployment $namespace/$name");

        $deployment = Deployment::on(Database::connection())
            ->filter(Filter::equal('deployment.id', $id))
            ->first();

        $this->addContent(new DeploymentDetail($deployment));
    }

    protected function getPageSize($default)
    {
        return parent::getPageSize($default ?? 50);
    }
}

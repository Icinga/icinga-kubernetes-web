<?php

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Web\DeploymentDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class DeploymentController extends CompatController
{
    public function indexAction(): void
    {
        $namespace = $this->params->getRequired('namespace');
        $name = $this->params->getRequired('name');
        $this->addTitleTab("Deployment $namespace/$name");

        $deployment = Deployment::on(Database::connection())
            ->filter(Filter::equal('namespace', $namespace))
            ->filter(Filter::equal('name', $name))
            ->first();

        var_dump($deployment);

        $this->addContent(new DeploymentDetail($deployment));
    }
}
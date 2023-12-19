<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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
        $this->addTitleTab($this->translate('Deployment'));

        /** @var Deployment $deployment */
        $deployment = Deployment::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($deployment === null) {
            $this->httpNotFound($this->translate('Deployment not found'));
        }

        $this->addContent(new DeploymentDetail($deployment));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\DeploymentDetail;
use Icinga\Module\Kubernetes\Web\DeploymentList;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class DeploymentController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Deployment'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var Deployment $deployment */
        $deployment = Deployment::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($deployment === null) {
            $this->httpNotFound($this->translate('Deployment not found'));
        }

        $this->addControl(new DeploymentList([$deployment]));

        $this->addContent(new DeploymentDetail($deployment));
    }
}

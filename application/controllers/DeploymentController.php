<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\DeploymentDetail;
use Icinga\Module\Kubernetes\Web\DeploymentList;
use Icinga\Module\Kubernetes\Web\ViewModeSwitcher;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class DeploymentController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_DEPLOYMENTS);

        $this->addTitleTab($this->translate('Deployment'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $deployment = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_DEPLOYMENTS, Deployment::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($deployment === null) {
            $this->httpNotFound($this->translate('Deployment not found'));
        }

        $this->addControl(
            (new DeploymentList([$deployment]))
                ->setActionList(false)
                ->setViewMode(ViewModeSwitcher::VIEW_MODE_DETAILED)
        );

        $this->addContent(new DeploymentDetail($deployment));
    }
}

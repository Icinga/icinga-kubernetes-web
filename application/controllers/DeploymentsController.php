<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Web\DeploymentList;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class DeploymentsController extends ListController
{
    protected function getContentClass(): string
    {
        return DeploymentList::class;
    }

    protected function getQuery(): Query
    {
        return Deployment::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'deployment.created desc' => $this->translate('Created'),
            'deployment.name'         => $this->translate('Name'),
            'deployment.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Deployments');
    }

    protected function getPermission(): string
    {
        return Auth::SHOW_DEPLOYMENTS;
    }
}

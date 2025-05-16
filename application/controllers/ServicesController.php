<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class ServicesController extends ListController
{
    protected function getQuery(): Query
    {
        return Service::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'service.created desc' => $this->translate('Created'),
            'service.name'         => $this->translate('Name'),
            'service.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Services');
    }

    protected function getPermission(): string
    {
        return Auth::SHOW_SERVICES;
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Web\Controller\ListController;
use ipl\Orm\Query;

class NamespacesController extends ListController
{
    protected function getQuery(): Query
    {
        return NamespaceModel::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'namespaces.created desc' => $this->translate('Created'),
            'namespaces.name'         => $this->translate('Name')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Namespaces');
    }

    protected function getPermission(): string
    {
        return Auth::SHOW_NAMESPACES;
    }

    protected function getFavorable(): true
    {
        return true;
    }
}

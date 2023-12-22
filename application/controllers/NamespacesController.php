<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Web\ListController;
use Icinga\Module\Kubernetes\Web\NamespaceList;
use ipl\Orm\Query;

class NamespacesController extends ListController
{
    protected function getContentClass(): string
    {
        return NamespaceList::class;
    }

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
}

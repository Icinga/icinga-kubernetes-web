<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class NodesController extends ListController
{
    protected function getQuery(): Query
    {
        return Node::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'node.created desc' => $this->translate('Created'),
            'node.name'         => $this->translate('Name')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Nodes');
    }

    protected function getPermission(): string
    {
        return Auth::SHOW_NODES;
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Web\ListController;
use Icinga\Module\Kubernetes\Web\NodeList;
use ipl\Orm\Query;

class NodesController extends ListController
{
    protected function getContentClass(): string
    {
        return NodeList::class;
    }

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
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Web\ListController;
use Icinga\Module\Kubernetes\Web\ReplicaSetList;
use ipl\Orm\Query;

class ReplicasetsController extends ListController
{
    protected function getContentClass(): string
    {
        return ReplicaSetList::class;
    }

    protected function getQuery(): Query
    {
        return ReplicaSet::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'replica_set.created desc' => $this->translate('Created'),
            'replica_set.name'         => $this->translate('Name'),
            'replica_set.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Replica Sets');
    }
}

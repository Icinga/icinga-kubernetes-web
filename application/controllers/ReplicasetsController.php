<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class ReplicasetsController extends ListController
{
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

    protected function getPermission(): string
    {
        return Auth::SHOW_REPLICA_SETS;
    }
}

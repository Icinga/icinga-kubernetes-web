<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Web\ListController;
use Icinga\Module\Kubernetes\Web\StatefulSetList;
use ipl\Orm\Query;

class StatefulsetsController extends ListController
{
    protected function getContentClass(): string
    {
        return StatefulSetList::class;
    }

    protected function getQuery(): Query
    {
        return StatefulSet::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'stateful_set.created desc' => $this->translate('Created'),
            'stateful_set.name'         => $this->translate('Name'),
            'stateful_set.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Stateful Sets');
    }
}

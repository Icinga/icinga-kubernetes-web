<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class StatefulsetsController extends ListController
{
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

    protected function getPermission(): string
    {
        return Auth::SHOW_STATEFUL_SETS;
    }

    protected function getIgnoredViewModes(): array
    {
        return [];
    }
}

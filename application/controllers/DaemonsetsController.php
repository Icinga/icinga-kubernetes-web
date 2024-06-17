<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Web\DaemonSetList;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class DaemonsetsController extends ListController
{
    protected function getContentClass(): string
    {
        return DaemonSetList::class;
    }

    protected function getQuery(): Query
    {
        return DaemonSet::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'daemon_set.created desc' => $this->translate('Created'),
            'daemon_set.name'         => $this->translate('Name'),
            'daemon_set.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Daemon Sets');
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Web\ListController;
use Icinga\Module\Kubernetes\Web\PersistentVolumeList;
use ipl\Orm\Query;

class PersistentvolumesController extends ListController
{
    protected function getContentClass(): string
    {
        return PersistentVolumeList::class;
    }

    protected function getQuery(): Query
    {
        return PersistentVolume::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'persistent_volume.created desc' => $this->translate('Created'),
            'persistent_volume.name'         => $this->translate('Name'),
            'persistent_volume.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Persistent Volumes');
    }

    protected function getPermission(): string
    {
        return Auth::SHOW_PERSISTENT_VOLUMES;
    }
}

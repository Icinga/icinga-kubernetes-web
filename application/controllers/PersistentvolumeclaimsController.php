<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Web\ListController;
use Icinga\Module\Kubernetes\Web\PersistentVolumeClaimList;
use ipl\Orm\Query;

class PersistentvolumeclaimsController extends ListController
{
    protected function getContentClass(): string
    {
        return PersistentVolumeClaimList::class;
    }

    protected function getQuery(): Query
    {
        return PersistentVolumeClaim::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'pvc.created desc' => $this->translate('Created'),
            'pvc.name'         => $this->translate('Name'),
            'pvc.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Persistent Volume Claims');
    }
}

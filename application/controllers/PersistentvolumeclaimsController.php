<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Web\Controller\ListController;
use ipl\Orm\Query;

class PersistentvolumeclaimsController extends ListController
{
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

    protected function getPermission(): string
    {
        return Auth::SHOW_PERSISTENT_VOLUME_CLAIMS;
    }

    protected function getIgnoredViewModes(): array
    {
        return [ViewMode::Detailed];
    }

    protected function getFavorable(): true
    {
        return true;
    }
}

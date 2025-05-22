<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class IngressesController extends ListController
{
    protected function getQuery(): Query
    {
        return Ingress::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'ingress.created desc' => $this->translate('Created'),
            'ingress.name'         => $this->translate('Name'),
            'ingress.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Ingresses');
    }

    protected function getPermission(): string
    {
        return Auth::SHOW_INGRESSES;
    }

    protected function getIgnoredViewModes(): array
    {
        return [ViewMode::Detailed];
    }
}

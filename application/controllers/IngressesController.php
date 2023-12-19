<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Web\IngressList;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class IngressesController extends ListController
{
    protected function getContentClass(): string
    {
        return IngressList::class;
    }

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
}

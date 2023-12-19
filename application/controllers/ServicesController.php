<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Web\ListController;
use Icinga\Module\Kubernetes\Web\ServiceList;
use ipl\Orm\Query;

class ServicesController extends ListController
{
    protected function getContentClass(): string
    {
        return ServiceList::class;
    }

    protected function getQuery(): Query
    {
        return Service::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'service.created desc' => $this->translate('Created'),
            'service.name'         => $this->translate('Name'),
            'service.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Services');
    }
}

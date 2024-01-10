<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Web\ConfigMapList;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class ConfigmapsController extends ListController
{
    protected function getContentClass(): string
    {
        return ConfigMapList::class;
    }

    protected function getQuery(): Query
    {
        return ConfigMap::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'config_map.created desc' => $this->translate('Created'),
            'config_map.name'         => $this->translate('Name'),
            'config_map.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Config Maps');
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
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
        $configMaps = ConfigMap::on(Database::connection());

        Auth::getInstance()->applyRestrictions($configMaps);

        return $configMaps;
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

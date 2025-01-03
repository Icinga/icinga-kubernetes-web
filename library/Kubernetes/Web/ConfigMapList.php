<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class ConfigMapList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'config-map-list'];

    protected function getItemClass(): string
    {
        return ConfigMapListItemMinimal::class;
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class ConfigMapList extends BaseItemList
{
    protected function getItemClass(): string
    {
        return ConfigMapListItem::class;
    }
}

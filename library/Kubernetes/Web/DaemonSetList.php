<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class DaemonSetList  extends BaseItemList
{
    protected function getItemClass(): string
    {
        return DaemonSetListItem::class;
    }
}

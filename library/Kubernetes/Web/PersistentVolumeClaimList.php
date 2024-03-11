<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class PersistentVolumeClaimList extends BaseItemList
{
    protected function getItemClass(): string
    {
        return PersistentVolumeClaimListItem::class;
    }
}

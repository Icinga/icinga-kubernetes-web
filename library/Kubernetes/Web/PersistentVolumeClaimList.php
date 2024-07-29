<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class PersistentVolumeClaimList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'pvc-list'];

    protected function getItemClass(): string
    {
        return PersistentVolumeClaimListItem::class;
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class PersistentVolumeClaimList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'pvc-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => PersistentVolumeClaimListItemMinimal::class,
            'detailed' => PersistentVolumeClaimListItemDetailed::class,
            default    => PersistentVolumeClaimListItem::class,
        };
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class PersistentVolumeList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'persistent-volume-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => PersistentVolumeListItemMinimal::class,
            'detailed' => PersistentVolumeListItemDetailed::class,
            default    => PersistentVolumeListItem::class,
        };
    }
}

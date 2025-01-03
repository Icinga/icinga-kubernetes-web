<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class PodList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'pod-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => PodListItemMinimal::class,
            'detailed' => PodListItemDetailed::class,
            default    => PodListItem::class,
        };
    }
}

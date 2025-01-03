<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class StatefulSetList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'stateful-set-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => StatefulSetListItemMinimal::class,
            'detailed' => StatefulSetListItemDetailed::class,
            default    => StatefulSetListItem::class,
        };
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class ServiceList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'service-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => ServiceListItemMinimal::class,
            'detailed' => ServiceListItemDetailed::class,
            default    => ServiceListItem::class,
        };
    }
}

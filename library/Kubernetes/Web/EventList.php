<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class EventList extends BaseItemList
{
    use ViewMode;

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal' => EventListItemMinimal::class,
            default   => EventListItem::class,
        };
    }
}

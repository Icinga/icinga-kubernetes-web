<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class EventList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'event-list'];

    protected function getItemClass(): string
    {
        return EventListItem::class;
    }
}

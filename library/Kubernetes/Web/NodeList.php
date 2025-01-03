<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class NodeList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'node-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => NodeListItemMinimal::class,
            'detailed' => NodeListItemDetailed::class,
            default    => NodeListItem::class,
        };
    }
}

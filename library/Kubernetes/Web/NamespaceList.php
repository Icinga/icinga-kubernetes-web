<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class NamespaceList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'namespace-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => NamespaceListItemMinimal::class,
            'detailed' => NamespaceListItemDetailed::class,
            default    => NamespaceListItem::class,
        };
    }
}

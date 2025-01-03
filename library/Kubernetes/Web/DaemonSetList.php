<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class DaemonSetList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'daemon-set-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => DaemonSetListItemMinimal::class,
            'detailed' => DaemonSetListItemDetailed::class,
            default    => DaemonSetListItem::class,
        };
    }
}

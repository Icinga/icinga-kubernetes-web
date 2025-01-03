<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class SecretList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'secret-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal' => SecretListItemMinimal::class,
            default   => SecretListItem::class,
        };
    }
}

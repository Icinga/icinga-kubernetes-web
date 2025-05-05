<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ViewMode;

class InitContainerList extends ContainerList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'init-container-list'];

    protected function getItemClass(): string
    {
        return InitContainerListItem::class;
    }
}

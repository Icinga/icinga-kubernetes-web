<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

class InitContainerList extends ContainerList
{
    protected $defaultAttributes = ['class' => 'init-container-list'];

    protected function getItemClass(): string
    {
        return InitContainerListItem::class;
    }
}

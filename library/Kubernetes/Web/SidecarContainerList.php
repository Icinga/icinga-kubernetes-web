<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

class SidecarContainerList extends ContainerList
{
    protected function getItemClass(): string
    {
        return SidecarContainerListItem::class;
    }
}

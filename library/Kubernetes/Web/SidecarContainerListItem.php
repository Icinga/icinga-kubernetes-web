<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Links;
use ipl\Web\Url;

class SidecarContainerListItem extends ContainerListItem
{
    protected function getDetailUrl(): Url
    {
        return Links::sidecarContainer($this->item);
    }
}

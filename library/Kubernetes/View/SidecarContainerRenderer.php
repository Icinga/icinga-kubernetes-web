<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use ipl\Web\Url;

class SidecarContainerRenderer extends ContainerRenderer
{
    protected function getDetailUrl($item): Url
    {
        return Links::sidecarContainer($item);
    }
}

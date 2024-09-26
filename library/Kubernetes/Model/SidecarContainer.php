<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

class SidecarContainer extends Container
{
    public function getTableName(): string
    {
        return 'sidecar_container';
    }
}

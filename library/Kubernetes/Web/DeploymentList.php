<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class DeploymentList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'deployment-list'];

    protected function getItemClass(): string
    {
        return DeploymentListItem::class;
    }
}

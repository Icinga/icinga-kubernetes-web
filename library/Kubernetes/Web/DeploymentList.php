<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class DeploymentList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'deployment-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => DeploymentListItemMinimal::class,
            'detailed' => DeploymentListItemDetailed::class,
            default    => DeploymentListItem::class,
        };
    }
}

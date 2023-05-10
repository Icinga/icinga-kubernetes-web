<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class PodList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'pod-list'];

    protected function getItemClass(): string
    {
        return PodListItem::class;
    }
}

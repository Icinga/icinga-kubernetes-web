<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class ReplicaSetList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'replica-set-list'];

    protected function getItemClass(): string
    {
        return ReplicaSetListItem::class;
    }
}

<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class StatefulSetList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'statefulset-list'];

    protected function getItemClass(): string
    {
        return StatefulSetListItem::class;
    }
}
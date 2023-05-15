<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class DaemonSetList  extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'daemon-set-list'];

    protected function getItemClass(): string
    {
        return DaemonSetListItem::class;
    }
}

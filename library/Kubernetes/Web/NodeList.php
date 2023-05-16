<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class NodeList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'node-list'];

    protected function getItemClass(): string
    {
        return NodeListItem::class;
    }
}

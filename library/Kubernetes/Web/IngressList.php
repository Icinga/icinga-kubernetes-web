<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class IngressList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'ingress-list'];

    protected function getItemClass(): string
    {
        return IngressListItem::class;
    }
}

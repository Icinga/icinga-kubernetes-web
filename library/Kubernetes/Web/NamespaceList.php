<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class NamespaceList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'namespace-list'];

    protected function getItemClass(): string
    {
        return NamespaceListItem::class;
    }
}

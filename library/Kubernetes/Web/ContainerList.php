<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class ContainerList extends BaseItemList
{
    /**
     * Copied from {@see BaseItemList::$baseAttributes} but removed `action-list` as
     * multiple action lists in a single view does not seem to work.
     *
     * @var array
     */
    protected array $baseAttributes = [
        'class'                         => 'item-list',
        'data-base-target'              => '_next',
        'data-pdfexport-page-breaks-at' => '.list-item'
    ];

    protected $defaultAttributes = ['class' => 'container-list'];

    protected function getItemClass(): string
    {
        return ContainerListItem::class;
    }
}

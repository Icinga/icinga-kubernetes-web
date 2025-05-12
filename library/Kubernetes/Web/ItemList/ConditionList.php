<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\ItemList;

use Icinga\Module\Kubernetes\View\ConditionItemLayout;
use Icinga\Module\Kubernetes\View\ConditionRenderer;
use ipl\Web\Widget\ItemList;

class ConditionList extends ItemList
{
    public function __construct($conditions, callable $getVisualFunc)
    {
        parent::__construct($conditions, new ConditionRenderer($getVisualFunc));

        $this->setItemLayoutClass(ConditionItemLayout::class);
        $this->addAttributes(['class' => 'conditions']);
    }
}

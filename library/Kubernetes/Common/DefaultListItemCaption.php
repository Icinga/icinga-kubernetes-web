<?php

namespace Icinga\Module\Kubernetes\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Text;

trait DefaultListItemCaption
{
    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new Text($this->item->icinga_state_reason));
    }
}

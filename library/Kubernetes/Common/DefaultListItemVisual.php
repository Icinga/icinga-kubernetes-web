<?php

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Web\FavoriteToggleForm;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\StateBall;

trait DefaultListItemVisual
{
    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class EventListItem extends BaseListItem
{
    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $caption->addHtml(new Text($this->item->note));
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header
            ->addHtml($this->createTitle())
            ->addHtml(new TimeAgo($this->item->last_seen->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());
        $main->addHtml($this->createCaption());
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(new Link($this->item->reason, Links::event($this->item), ['class' => 'subject']));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $typeVisual = $this->createTypeVisual();
        if ($typeVisual !== null) {
            $visual->addHtml($typeVisual);
        }
    }

    protected function createTypeVisual(): ?ValidHtml
    {
        switch ($this->item->type) {
            case 'Warning':
                return new StateBall('warning', StateBall::SIZE_MEDIUM);
            default:
                return null;
        }
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class EventListItem extends BaseListItem
{
    use Translation;

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
        $title->addHtml(Html::sprintf(
            $this->translate('%s %s', '<pod> is <icinga_state>'),
            new HtmlElement('a', new Attributes(['class' => 'subject']), new Text($this->item->reason)),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text("namespace")
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
                        new Text("pod")
                    ),
                    Links::event($this->item),
                )
            ]
        ));
        //$title->addHtml(new Link($this->item->reason, Links::event($this->item), ['class' => 'subject']));
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
                return new StateBall('none', StateBall::SIZE_MEDIUM);
        }
    }
}

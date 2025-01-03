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
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

class NodeListItemMinimal extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml(
            Html::tag(
                'span',
                Attributes::create(['class' => 'header-minimal']),
                [
                    $this->createTitle(),
                    $this->createCaption()
                ]
            )
        );
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new Text($this->item->icinga_state_reason));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml(
            $this->createHeader(),
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<node> is <icinga_state>'),
            new Link(
                (new HtmlDocument())->addHtml(
                    new Icon('share-nodes'),
                    new Text($this->item->name)
                ),
                Links::node($this->item),
                new Attributes(['class' => 'subject'])
            ),
            new HtmlElement(
                'span',
                new Attributes(['class' => 'icinga-state-text']),
                new Text($this->item->icinga_state)
            )
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

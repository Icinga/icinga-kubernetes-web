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
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class CronJobListItemMinimal extends BaseListItem
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
            ),
            new TimeAgo($this->item->created->getTimestamp())
        );
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        // TODO add state reason
        $caption->addHtml(new Text('Placeholder for Icinga State Reason'));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml(
            $this->createHeader(),
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(
            new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                new Text($this->item->namespace)
            ),
            new Link(
                (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-cronjob'])),
                    new Text($this->item->name)
                ),
                Links::cronJob($this->item),
                new Attributes(['class' => 'subject'])
            )
        );
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall('none', StateBall::SIZE_MEDIUM));
    }
}

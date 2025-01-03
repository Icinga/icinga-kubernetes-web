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

class StatefulSetListItemMinimal extends BaseListItem
{
    use Translation;

    public const UPDATE_STRATEGY_ICONS = [
        'RollingUpdate' => 'repeat',
        'OnDelete'      => 'trash'
    ];

    public const MANAGEMENT_POLICY_ICONS = [
        'OrderedReady' => 'shuffle',
        'Parallel'     => 'grip-lines'
    ];

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
            $this->translate('%s is %s', '<stateful_set> is <icinga_state>'),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text($this->item->namespace)
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new HtmlElement('i', new Attributes(['class' => 'icon kicon-stateful-set'])),
                        new Text($this->item->name)
                    ),
                    Links::statefulSet($this->item),
                    new Attributes(['class' => 'subject'])
                )
            ],
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

<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Web\Factory;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

abstract class BaseResourceRenderer implements ItemRenderer
{
    use Translation;

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $visual->addHtml(new StateBall($item->icinga_state, StateBall::SIZE_MEDIUM));
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(new Text($item->icinga_state_reason));
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $kind = Factory::canonicalizeKind($item->getTableAlias());
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', "<$kind> is <icinga_state>"),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text($item->namespace)
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new HtmlElement('i', new Attributes(['class' => "icon kicon-$kind"])),
                        new Text($item->name)
                    ),
                    Links::$kind($item),
                    new Attributes(['class' => 'subject'])
                )
            ],
            new HtmlElement(
                'span',
                new Attributes(['class' => 'icinga-state-text']),
                new Text($item->icinga_state)
            )
        ));
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        $info->addHtml(HtmlElement::create(
            'span',
            Attributes::create(['class' => 'info-container']),
            [
                new Icon('star', Attributes::create(['class' => 'favor-icon'])),
                new TimeAgo($item->created->getTimestamp())
            ]
        ));
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        return false;
    }
}

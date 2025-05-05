<?php

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Web\Factory;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Link;

trait DefaultListItemTitle
{
    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $kind = Factory::canonicalizeKind($this->item->getTableAlias());
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', "<$kind> is <icinga_state>"),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text($this->item->namespace)
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new HtmlElement('i', new Attributes(['class' => "icon kicon-$kind"])),
                        new Text($this->item->name)
                    ),
                    Links::$kind($this->item),
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
}

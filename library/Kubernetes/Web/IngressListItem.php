<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class IngressListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml(
            $this->createTitle(),
            new TimeAgo($this->item->created->getTimestamp())
        );
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml(
            $this->createHeader(),
            $this->createFooter()
        );
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $hosts = [];
        foreach ($this->item->ingress_rule as $rule) {
            if ($rule->host !== null) {
                $hosts[] = $rule->host;
            }
        }

        $footer->addHtml(
            new HorizontalKeyValue(
                $this->translate('Host'),
                ! empty($hosts) ? implode(', ', $hosts) : '-'
            )
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
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-ingress'])),
                    new Text($this->item->name)
                ),
                Links::ingress($this->item),
                new Attributes(['class' => 'subject'])
            )
        );
    }
}

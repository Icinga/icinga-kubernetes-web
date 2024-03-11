<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class IngressListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header
            ->addHtml($this->createTitle())
            ->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {

        $main->addHtml($this->createHeader());

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        foreach ($this->item->ingress_rule as $rule) {
            $keyValue->addHtml(new VerticalKeyValue($this->translate('Host'), $rule->host ?: '-'));
        }
        $keyValue->addHtml(new VerticalKeyValue($this->translate('Namespace'), $this->item->namespace));
        $main->addHtml($keyValue);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(new Link($this->item->name, Links::ingress($this->item), ['class' => 'subject']));
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Ingress;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class IngressListItem extends BaseListItem
{
    /** @var $item Ingress The associated list item */
    /** @var $list IngressList The list where the item is part of */

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s', '<ingress>'),
            new Link(
                $this->item->name,
                Links::ingress($this->item),
                ['class' => 'subject']
            ),
        );

        $title->addHtml($content);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $header->add(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {

        $main->add($this->createHeader());
        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        foreach ($this->item->ingress_rule as $rule) {
            if ($rule->host === "") {
                $keyValue->add(new VerticalKeyValue('Host', '-'));
            } else {
                $keyValue->add(new VerticalKeyValue('Host', $rule->host));
            }
        }
        $keyValue->add(new VerticalKeyValue('Namespace', $this->item->namespace));
        $main->addHtml($keyValue);
    }
}

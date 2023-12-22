<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class ConfigMapListItem extends BaseListItem
{
    /** @var $item ConfigMap The associated list item */
    /** @var $list ConfigMapList The list where the item is part of */

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(new Link($this->item->name, Links::configMap($this->item), ['class' => 'subject']));
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
        $header->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new VerticalKeyValue('Namespace', $this->item->namespace));
        $main->addHtml($keyValue);
    }
}

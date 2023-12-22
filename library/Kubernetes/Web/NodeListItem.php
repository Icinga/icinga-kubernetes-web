<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\VerticalKeyValue;

class NodeListItem extends BaseListItem
{
    /** @var $item Node The associated list item */
    /** @var $list NodeList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new Icon(
            $this->getReadyIcon(),
            ['class' => ['node-' . ($this->item->ready ? 'ready' : 'not-ready')]]
        ));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            t('%s is %s', '<node> is <ready>'),
            new Link($this->item->name, Links::node($this->item), ['class' => 'subject']),
            new HtmlElement('span', null, new Text($this->item->ready ? t('ready') : t('not ready')))
        ));
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new VerticalKeyValue(t('CIDR'), $this->item->pod_cidr));
        $keyValue->addHtml(new VerticalKeyValue(t('Pod Capacity'), $this->item->pod_capacity));
        $keyValue->addHtml(new VerticalKeyValue(t('IPs Available'), $this->item->num_ips));
        $keyValue->addHtml(new VerticalKeyValue(t('CPU Capacity'), sprintf('%d cores', $this->item->cpu_allocatable / 1000)));
        $keyValue->addHtml(new VerticalKeyValue(t('Memory Capacity'), Format::bytes($this->item->memory_allocatable / 1000)));
        $main->addHtml($keyValue);
    }

    protected function getReadyIcon(): string
    {
        return $this->item->ready ? Icons::NODE_READY : Icons::NODE_NOT_READY;
    }
}

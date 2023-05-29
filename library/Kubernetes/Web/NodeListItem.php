<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Str;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class NodeListItem extends BaseListItem
{
    /** @var $item Node The associated list item */
    /** @var $list NodeList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml($this->createTypeVisual());
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<node> is <ready>'),
            new Link(
                $this->item->name,
                Links::node($this->item->name),
                ['class' => 'subject']
            ),
            new HtmlElement('span', null, new Text($this->item->ready ? t('ready') : t('not ready')))
        );

        $title->addHtml($content);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $main->addHtml($keyValue);
        $keyValue->add(new VerticalKeyValue('CIDR', $this->item->pod_cidr));
        $keyValue->add(new VerticalKeyValue('Pod Capacity', $this->item->pod_capacity));
        $keyValue->add(new VerticalKeyValue('IPs Available', $this->item->num_ips));
        $keyValue->add(new VerticalKeyValue('CPU Capacity', sprintf('%d cores', $this->item->cpu_allocatable / 1000)));
        $keyValue->add(new VerticalKeyValue('Memory Capacity', Format::bytes($this->item->memory_allocatable / 1000)));
    }

    protected function createTypeVisual(): ?ValidHtml
    {
        if (! $this->item->ready) {
            return new StateBall('critical', StateBall::SIZE_MEDIUM);
        }

        return new StateBall('ok', StateBall::SIZE_MEDIUM);
    }
}

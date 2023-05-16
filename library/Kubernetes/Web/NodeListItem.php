<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Node;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class NodeListItem extends BaseListItem
{
    /** @var $item Node The associated list item */
    /** @var $list NodeList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $typeVisual = $this->createTypeVisual();
        if ($typeVisual !== null) {
            $visual->addHtml($typeVisual);
        }
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s', '<pod> is <pod_phase>'),
            new Link(
                $this->item->name,
                Links::node($this->item->name),
                ['class' => 'subject']
            ),
        );

        $title->addHtml($content);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $container = new HtmlElement('div', new Attributes(['style' => 'display: inline-flex; align-items: center;']));
        if ($this->item->cpu_capacity > 0) {
            $container->addHtml(new Usage($this->item->cpu_capacity, $this->item->cpu_allocatable));
        }
        if ($this->item->memory_capacity > 0) {
            $container->addHtml(new Usage($this->item->memory_capacity, $this->item->memory_allocatable));
        }
        $container->addHtml(new TimeAgo($this->item->created->getTimestamp()));
        $header->add($container);
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
    }

    protected function createTypeVisual(): ?ValidHtml
    {
        switch ($this->item->ready) {
            case 'Warning':
                return new StateBall('warning', StateBall::SIZE_MEDIUM);
            default:
                return null;
        }
    }
}

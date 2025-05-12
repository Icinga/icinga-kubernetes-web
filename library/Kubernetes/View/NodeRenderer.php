<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class NodeRenderer extends BaseResourceRenderer
{
    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $footer->addHtml(
            new HorizontalKeyValue($this->translate('CIDR'), $item->pod_cidr),
            new HorizontalKeyValue(
                $this->translate('CPU Capacity'),
                sprintf($this->translate('%d cores', 'number of CPU cores'), $item->cpu_allocatable / 1000)
            ),
            new HorizontalKeyValue(
                $this->translate('Memory Capacity'),
                Format::bytes($item->memory_allocatable / 1000)
            )
        );
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<node> is <icinga_state>'),
            new Link(
                (new HtmlDocument())->addHtml(
                    new Icon('share-nodes'),
                    new Text($item->name)
                ),
                Links::node($item),
                new Attributes(['class' => 'subject'])
            ),
            new HtmlElement(
                'span',
                new Attributes(['class' => 'icinga-state-text']),
                new Text($item->icinga_state)
            )
        ));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\DefaultListItemCaption;
use Icinga\Module\Kubernetes\Common\DefaultListItemHeader;
use Icinga\Module\Kubernetes\Common\DefaultListItemMain;
use Icinga\Module\Kubernetes\Common\DefaultListItemVisual;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class NodeListItem extends BaseListItem
{
    use Translation;
    use DefaultListItemHeader;
    use DefaultListItemCaption;
    use DefaultListItemMain;
    use DefaultListItemVisual;

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $footer->addHtml(
            new HorizontalKeyValue($this->translate('CIDR'), $this->item->pod_cidr),
            new HorizontalKeyValue(
                $this->translate('CPU Capacity'),
                sprintf($this->translate('%d cores', 'number of CPU cores'), $this->item->cpu_allocatable / 1000)
            ),
            new HorizontalKeyValue(
                $this->translate('Memory Capacity'),
                Format::bytes($this->item->memory_allocatable / 1000)
            )
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<node> is <icinga_state>'),
            new Link(
                (new HtmlDocument())->addHtml(
                    new Icon('share-nodes'),
                    new Text($this->item->name)
                ),
                Links::node($this->item),
                new Attributes(['class' => 'subject'])
            ),
            new HtmlElement(
                'span',
                new Attributes(['class' => 'icinga-state-text']),
                new Text($this->item->icinga_state)
            )
        ));
    }
}

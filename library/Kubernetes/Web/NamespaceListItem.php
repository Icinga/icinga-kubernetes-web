<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use LogicException;

class NamespaceListItem extends BaseListItem
{
    /** @var $item NamespaceModel The associated list item */
    /** @var $list NamespaceList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $content = new Icon($this->getPhaseIcon(), ['class' => ['phase-' . $this->item->phase]]);
        $visual->addHtml($content);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<name> is <phase>'),
            new Link(
                $this->item->name,
                Links::namespace($this->item),
                ['class' => 'subject']
            ),
            new HtmlElement('span', new Attributes(['class' => 'phase-text']), new Text($this->item->phase))
        );

        $title->addHtml($content);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $header->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
    }

    protected function getPhaseIcon(): string
    {
        switch ($this->item->phase) {
            case NamespaceModel::PHASE_ACTIVE:
                return Icons::NAMESPACE_ACTIVE;
            case NamespaceModel::PHASE_TERMINATING:
                return Icons::NAMESPACE_TERMINATING;
            default:
                throw new LogicException();
        }
    }
}

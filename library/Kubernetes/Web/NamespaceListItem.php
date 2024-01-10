<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class NamespaceListItem extends BaseListItem
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
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<namespace> is <namespace_phase>'),
            new Link($this->item->name, Links::namespace($this->item), ['class' => 'subject']),
            new HtmlElement('span', null, new Text($this->item->phase))
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new Icon($this->getPhaseIcon(), ['class' => ['namespace-phase-' . $this->item->phase]]));
    }

    protected function getPhaseIcon(): string
    {
        switch ($this->item->phase) {
            case NamespaceModel::PHASE_ACTIVE:
                return Icons::NAMESPACE_ACTIVE;
            case NamespaceModel::PHASE_TERMINATING:
                return Icons::NAMESPACE_TERMINATING;
            default:
                return Icons::BUG;
        }
    }
}

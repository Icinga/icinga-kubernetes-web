<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class PersistentVolumeClaimListItem extends BaseListItem
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
        $keyValue->addHtml(new VerticalKeyValue(
            $this->translate('Storage Class Name'),
            ucfirst(Str::camel($this->item->storage_class))
        ));
        $keyValue->addHtml(new VerticalKeyValue(
            $this->translate('Volume Mode'),
            ucfirst(Str::camel($this->item->getVolumeMode()))
        ));
        $keyValue->addHtml(new VerticalKeyValue(
            $this->translate('Capacity'),
            Format::bytes($this->item->actual_capacity / 1000)
        ));
        $keyValue->addHtml(new VerticalKeyValue(
            $this->translate('Access Modes'),
            implode(', ', AccessModes::asNames($this->item->actual_access_modes))
        ));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('Namespace'), $this->item->namespace));
        $main->addHtml($keyValue);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            $this->translate('%s is %s', '<pvc> is <pvc_phase>'),
            new Link($this->item->name, Links::pvc($this->item), ['class' => 'subject']),
            new HtmlElement('span', null, new Text($this->item->phase))
        );

        $title->addHtml($content);
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $content = new Icon($this->getPhaseIcon(), ['class' => ['phase-' . $this->item->phase]]);
        $visual->addHtml($content);
    }

    protected function getPhaseIcon(): string
    {
        switch ($this->item->phase) {
            case PersistentVolumeClaim::PHASE_PENDING:
                return Icons::PVC_PENDING;
            case PersistentVolumeClaim::PHASE_BOUND:
                return Icons::PVC_BOUND;
            case PersistentVolumeClaim::PHASE_LOST:
                return Icons::PVC_LOST;
            default:
                return Icons::BUG;
        }
    }
}

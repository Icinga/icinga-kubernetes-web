<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
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

class PersistentVolumeListItem extends BaseListItem
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
            Format::bytes($this->item->capacity / 1000)
        ));
        $keyValue->addHtml(new VerticalKeyValue(
            $this->translate('Access Mode'),
            implode(', ', AccessModes::asNames((int) $this->item->access_modes))
        ));
        $main->addHtml($keyValue);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<persistent_volume> is <persistent_volume_phase>'),
            new Link($this->item->name, Links::persistentVolume($this->item), ['class' => 'subject']),
            new HtmlElement('span', null, new Text($this->item->phase))
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new Icon($this->getPhaseIcon(), ['class' => ['phase-' . $this->item->phase]]));
    }

    protected function getPhaseIcon(): string
    {
        switch ($this->item->phase) {
            case PersistentVolume::PHASE_PENDING:
                return Icons::PV_PENDING;
            case PersistentVolume::PHASE_AVAILABLE:
                return Icons::PV_AVAILABLE;
            case PersistentVolume::PHASE_BOUND:
                return Icons::PV_BOUND;
            case PersistentVolume::PHASE_RELEASED:
                return Icons::PV_RELEASED;
            case PersistentVolume::PHASE_FAILED:
                return Icons::PV_FAILED;
            default:
                return Icons::BUG;
        }
    }
}

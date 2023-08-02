<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\TBD\AccessModes;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;
use LogicException;

class PersistentVolumeListItem extends BaseListItem
{
    /** @var $item PersistentVolume The associated list item */
    /** @var $list PersistentVolumeList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $content = new Icon($this->getPhaseIcon(), ['class' => ['phase-' . $this->item->phase]]);
        $visual->addHtml($content);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<persistent_volume> is <persistent_volume_phase>'),
            new Link(
                $this->item->name,
                Links::persistentVolume($this->item),
                ['class' => 'subject']
            ),
            new HtmlElement('span', new Attributes(['class' => 'phase-text']), new Text($this->item->phase))
        );

        $title->addHtml($content);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $header->add(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->add(new VerticalKeyValue('Storage Class Name', ucfirst(Str::camel($this->item->storage_class))));
        $volumeMode = $this->item->volume_mode ?? PersistentVolume::DEFAULT_VOLUME_MODE;
        $keyValue->add(new VerticalKeyValue('Volume Mode', ucfirst(Str::camel($volumeMode))));
        $keyValue->add(New VerticalKeyValue('Capacity', Format::bytes($this->item->capacity / 1000)));
        $keyValue->add(new VerticalKeyValue('Access Mode', implode(', ', AccessModes::asNames((int) $this->item->access_modes))));
        $main->add($keyValue);
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
                throw new LogicException();
        }
    }
}

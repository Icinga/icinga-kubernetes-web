<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
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

class PersistentVolumeClaimListItem extends BaseListItem
{
    /** @var $item PersistentVolumeClaim The associated list item */
    /** @var $list PersistentVolumeClaimList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $content = new Icon($this->getPhaseIcon(), ['class' => ['phase-' . $this->item->phase]]);
        $visual->addHtml($content);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<pvc> is <pvc_phase>'),
            new Link(
                $this->item->name,
                Links::pvc($this->item),
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
        $volumeMode = $this->item->volume_mode ?? PersistentVolumeClaim::DEFAULT_VOLUME_MODE;
        $keyValue->add(new VerticalKeyValue('Volume Mode', ucfirst(Str::camel($volumeMode))));
        $keyValue->add(New VerticalKeyValue('Capacity', Format::bytes($this->item->actual_capacity / 1000)));
        $keyValue->add(new VerticalKeyValue('Access Modes', implode(', ', AccessModes::asNames($this->item->actual_access_modes))));
        $keyValue->add(new VerticalKeyValue('Namespace', $this->item->namespace));

        $main->add($keyValue);
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
                throw new LogicException();
        }
    }
}

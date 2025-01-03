<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class PersistentVolumeListItemMinimal extends BaseListItem
{
    use Translation;

    protected function getPhaseIcon(): string
    {
        return match ($this->item->phase) {
            PersistentVolume::PHASE_PENDING   => Icons::PV_PENDING,
            PersistentVolume::PHASE_AVAILABLE => Icons::PV_AVAILABLE,
            PersistentVolume::PHASE_BOUND     => Icons::PV_BOUND,
            PersistentVolume::PHASE_RELEASED  => Icons::PV_RELEASED,
            PersistentVolume::PHASE_FAILED    => Icons::PV_FAILED,
            default                           => Icons::BUG
        };
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml(
            Html::tag('span',
                Attributes::create(['class' => 'header-minimal']),
                [
                    $this->createTitle(),
                    $this->createCaption()
                ]
            ),
            (new TimeAgo($this->item->created->getTimestamp()))
        );
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new Text('Placeholder for Icinga State Reason'));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml(
            $this->createHeader(),
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<persistent_volume> is <persistent_volume_phase>'),
            new Link(
                (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-persistent-volume'])),
                    new Text($this->item->name)
                ),
                Links::persistentVolume($this->item),
                new Attributes(['class' => 'subject'])
            ),
            new HtmlElement(
                'span',
                new Attributes(['class' => 'persistent-volume-phase']),
                new Text($this->item->phase)
            )
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(
            new Icon($this->getPhaseIcon(), ['class' => ['pv-phase-' . strtolower($this->item->phase)]])
        );
    }
}

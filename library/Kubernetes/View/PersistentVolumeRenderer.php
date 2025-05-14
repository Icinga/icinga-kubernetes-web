<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class PersistentVolumeRenderer extends BaseResourceRenderer
{
    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $visual->addHtml(
            new Icon($this->getPhaseIcon($item), ['class' => ['pv-phase-' . strtolower($item->phase)]])
        );
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(new Text($item->reason));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $footer->addHtml(
            new HorizontalKeyValue(
                $this->translate('Storage Class'),
                $item->storage_class
            ),
            new HorizontalKeyValue(
                $this->translate('Volume Mode'),
                $item->volume_mode
            ),
            new HorizontalKeyValue(
                $this->translate('Access Mode'),
                implode(', ', AccessModes::asNames((int) $item->access_modes))
            ),
            (new HorizontalKeyValue(
                $this->translate('Capacity'),
                Format::bytes($item->capacity / 1000)
            ))
                ->addAttributes(new Attributes(['class' => 'push-left']))
        );
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<persistent_volume> is <persistent_volume_phase>'),
            new Link(
                (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-persistent-volume'])),
                    new Text($item->name)
                ),
                Links::persistentvolume($item),
                new Attributes(['class' => 'subject'])
            ),
            new HtmlElement(
                'span',
                new Attributes(['class' => 'persistent-volume-phase']),
                new Text($item->phase)
            )
        ));
    }

    protected function getPhaseIcon($item): string
    {
        return match ($item->phase) {
            PersistentVolume::PHASE_PENDING   => Icons::PV_PENDING,
            PersistentVolume::PHASE_AVAILABLE => Icons::PV_AVAILABLE,
            PersistentVolume::PHASE_BOUND     => Icons::PV_BOUND,
            PersistentVolume::PHASE_RELEASED  => Icons::PV_RELEASED,
            PersistentVolume::PHASE_FAILED    => Icons::PV_FAILED,
            default                           => Icons::BUG
        };
    }
}

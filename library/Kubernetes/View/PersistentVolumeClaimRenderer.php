<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Web\KIcon;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class PersistentVolumeClaimRenderer extends BaseResourceRenderer
{
    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $visual->addHtml(
            new Icon($this->getPhaseIcon($item), ['class' => ['pvc-phase-' . strtolower($item->phase)]])
        );
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        // TODO add state reason then remove this function
        $caption->addHtml(new Text('Placeholder for Icinga State Reason'));
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
                $this->translate('Access Modes'),
                $item->actual_access_modes !== null ?
                    implode(', ', AccessModes::asNames($item->actual_access_modes)) :
                    new EmptyState($this->translate('None'))
            ),
            (new HorizontalKeyValue(
                $this->translate('Capacity'),
                $item->actual_capacity !== null ?
                    Format::bytes($item->actual_capacity / 1000) :
                    new EmptyState($this->translate('None'))
            ))
                ->addAttributes(new Attributes(['class' => 'push-left']))
        );
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<pvc> is <pvc_phase'),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new KIcon('namespace'),
                    new Text($item->namespace)
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new KIcon('pvc'),
                        new Text($item->name)
                    ),
                    Links::persistentvolumeclaim($item),
                    new Attributes(['class' => 'subject'])
                )
            ],
            new HtmlElement(
                'span',
                new Attributes(['class' => 'persistent-volume-claim-phase']),
                new Text($item->phase)
            )
        ));
    }

    protected function getPhaseIcon($item): string
    {
        return match ($item->phase) {
            PersistentVolumeClaim::PHASE_PENDING => Icons::PVC_PENDING,
            PersistentVolumeClaim::PHASE_BOUND   => Icons::PVC_BOUND,
            PersistentVolumeClaim::PHASE_LOST    => Icons::PVC_LOST,
            default                              => Icons::BUG
        };
    }
}

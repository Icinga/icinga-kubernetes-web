<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

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
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class PersistentVolumeClaimListItem extends BaseListItem
{
    use Translation;

    protected function getPhaseIcon(): string
    {
        return match ($this->item->phase) {
            PersistentVolumeClaim::PHASE_PENDING => Icons::PVC_PENDING,
            PersistentVolumeClaim::PHASE_BOUND   => Icons::PVC_BOUND,
            PersistentVolumeClaim::PHASE_LOST    => Icons::PVC_LOST,
            default                              => Icons::BUG
        };
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml(
            Html::tag(
                'span',
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
            $this->createFooter()
        );
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $footer->addHtml(
            new HorizontalKeyValue(
                $this->translate('Storage Class'),
                $this->item->storage_class
            ),
            new HorizontalKeyValue(
                $this->translate('Volume Mode'),
                $this->item->volume_mode
            ),
            new HorizontalKeyValue(
                $this->translate('Access Modes'),
                $this->item->actual_access_modes !== null ?
                    implode(', ', AccessModes::asNames($this->item->actual_access_modes)) :
                    new EmptyState($this->translate('None'))
            ),
            (new HorizontalKeyValue(
                $this->translate('Capacity'),
                $this->item->actual_capacity !== null ?
                    Format::bytes($this->item->actual_capacity / 1000) :
                    new EmptyState($this->translate('None'))
            ))
                ->addAttributes(new Attributes(['class' => 'push-left']))
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<pvc> is <pvc_phase'),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text($this->item->namespace)
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new HtmlElement('i', new Attributes(['class' => 'icon kicon-pvc'])),
                        new Text($this->item->name)
                    ),
                    Links::pvc($this->item),
                    new Attributes(['class' => 'subject'])
                )
            ],
            new HtmlElement(
                'span',
                new Attributes(['class' => 'persistent-volume-claim-phase']),
                new Text($this->item->phase)
            )
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(
            new Icon($this->getPhaseIcon(), ['class' => ['pvc-phase-' . strtolower($this->item->phase)]])
        );
    }
}

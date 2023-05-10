<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Common\Icons;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeSince;

/**
 * Event item of an event list. Represents one database row.
 */
class PodListItem extends BaseListItem
{
    /** @var $item Pod The associated list item */
    /** @var $list PodList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $content = new Icon($this->getPhaseIcon(), ['class' => ['phase-' . $this->item->phase]]);
//
//        if ($this->item->severity === 'ok' || $this->item->severity === 'err') {
//            $content->setStyle('fa-regular');
//        }

        $visual->addHtml($content);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s on %s is %s', '<pod> on <node> is <pod_phase>'),
            new Link(
                $this->item->name,
                Links::pod($this->item->namespace, $this->item->name),
                ['class' => 'subject']
            ),
            'node',
            Html::tag('span', ['class' => 'phase-text'], $this->item->phase)
//            new Link(
//                $this->item->node->name,
//                Links::node($this->item->node->name),
//                ['class' => 'subject']
//            )
        );

        $title->addHtml($content);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $header->add(new TimeAgo($this->item->created->getTimestamp()));

//        if ($this->item->recovered_at !== null) {
//            $header->add(Html::tag(
//                'span',
//                ['class' => 'meta'],
//                [
//                    'closed ',
//                    new TimeAgo($this->item->recovered_at->getTimestamp())
//                ]
//            ));
//        } else {
//            $header->add(new TimeSince($this->item->created->getTimestamp()));
//        }
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
    }

        protected function getPhaseIcon(): string
    {
        switch ($this->item->phase) {
            case Pod::PHASE_PENDING:
                return Icons::POD_PENDING;
            case Pod::PHASE_RUNNING:
                return Icons::POD_RUNNING;
            case Pod::PHASE_SUCCEEDED:
                return Icons::POD_SUCCEEDED;
            case Pod::PHASE_FAILED:
                return Icons::POD_FAILED;
            default:
                throw new InvalidArgumentException();
        }
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use DateTime;
use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\Event;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;
use LogicException;

class EventListItem extends BaseListItem
{
    /** @var $item Event The associated list item */
    /** @var $list EventList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $typeVisual = $this->createTypeVisual();
        if ($typeVisual !== null) {
            $visual->addHtml($typeVisual);
        }
//
//        if ($this->item->severity === 'ok' || $this->item->severity === 'err') {
//            $content->setStyle('fa-regular');
//        }
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(new Text($this->item->reason));
        return;
        $title->addHtml(Html::sprintf(
            t('%s is %s', '<container> is <container_state>'),
            new Link(
                $this->item->name,
                Links::pod("namespace", $this->item->name),
                ['class' => 'subject']
            ),
            new HtmlElement('span', new Attributes(['class' => 'state-text']), new Text($this->item->state))
        ));
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

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $caption->addHtml(new Text($this->item->note));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());
        $main->addHtml($this->createCaption());
    }

    protected function getStateIcon(): string
    {
        switch ($this->item->state) {
            case Container::STATE_WAITING:
                return Icons::POD_PENDING;
            case Container::STATE_RUNNING:
                return Icons::POD_RUNNING;
            case Container::STATE_TERMINATED:
                return Icons::POD_SUCCEEDED;
            default:
                throw new LogicException();
        }
    }

    protected function createStateDetails(): ValidHtml
    {
        $stateDetails = json_decode($this->item->state_details);

        switch ($this->item->state) {
            case Container::STATE_RUNNING:
                return new VerticalKeyValue('Started', new TimeAgo((new DateTime($stateDetails->startedAt))->getTimestamp()));
            case Container::STATE_TERMINATED:
            case Container::STATE_WAITING:
                return new HtmlElement(
                    'div',
                    null,
                    new HorizontalKeyValue('Reason', $stateDetails->reason),
                    new HorizontalKeyValue('Message', $stateDetails->message)
                );
            default:
                throw new LogicException();
        }
    }

    protected function createTypeVisual(): ?ValidHtml
    {
        switch ($this->item->type) {
            case 'Warning':
                return new StateBall('warning', StateBall::SIZE_MEDIUM);
            default:
                return null;
        }
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use DateTime;
use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Container;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;
use LogicException;

class ContainerListItem extends BaseListItem
{
    /** @var $item Container The associated list item */
    /** @var $list ContainerList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $content = new Icon($this->getStateIcon(), ['class' => ['state-' . $this->item->state]]);
//
//        if ($this->item->severity === 'ok' || $this->item->severity === 'err') {
//            $content->setStyle('fa-regular');
//        }

        $visual->addHtml($content);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<container> is <container_state>'),
            new Link(
                $this->item->name,
                Links::container($this->item->name),
                ['class' => 'subject']
            ),
            new HtmlElement('span', new Attributes(['class' => 'state-text']), new Text($this->item->state))
        );

        $title->addHtml($content);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        //$header->add(new TimeAgo($this->item->created->getTimestamp()));

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
        $main->addHtml($this->createHeader());
        $stateDetails = json_decode($this->item->state_details);
        if (isset($stateDetails->message)) {
            $main->addHtml(new HtmlElement('p', null, new Text($stateDetails->message)));
        }

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->add(new HtmlElement(
            'div',
            null,
            new HorizontalKeyValue('Started', new Icon($this->item->started ? 'check' : 'xmark')),
            new HorizontalKeyValue('Ready', new Icon($this->item->started ? 'check' : 'xmark'))
        ));
        $keyValue->add($this->createStateDetails());
        $keyValue->addHtml(new VerticalKeyValue('Image', $this->item->image));
        $keyValue->addHtml(new VerticalKeyValue('Restarts', $this->item->restart_count));
        $main->addHtml($keyValue);
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
                    new VerticalKeyValue('Reason', $stateDetails->reason)
                    //new HorizontalKeyValue('Message', $stateDetails->message)
                );
            default:
                throw new LogicException();
        }
    }
}

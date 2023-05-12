<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class StatefulSetListItem extends BaseListItem
{
    /** @var $item StatefulSet The associated list item */
    /** @var $list StatefulSetList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $content = new Icon($this->getHealthIcon());
//
//        if ($this->item->severity === 'ok' || $this->item->severity === 'err') {
//            $content->setStyle('fa-regular');
//        }

        $visual->addHtml($content);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<stateful_set> is <health>'),
            new Link(
                $this->item->name,
                Links::statefulSet($this->item->namespace, $this->item->name),
                ['class' => 'subject']
            ),
            Html::tag('span', ['class' => 'statefulset-text'], $this->getHealthIcon())
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
    }

    protected function getHealthIcon(): string
    {
        if ($this->item->ready_replicas !== $this->item->replicas &&
            $this->item->current_replicas !== 0) {
            return Icons::STATEFULSET_UNHEALTHY;
        } else if ($this->item->ready_replicas === $this->item->replicas) {
            return Icons::STATEFULSET_HEALTHY;
        } else if ($this->item->ready_replicas === 0 && $this->item->replicas !== 0) {
            return Icons::STATEFULSET_CRITICAL;
        } else {
            return Icons::STATEFULSET_UNKNOWN;
        }
    }
}
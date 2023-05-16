<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class ReplicaSetListItem extends BaseListItem
{
    /** @var $item ReplicaSet The associated list item */
    /** @var $list ReplicaSetList The list where the item is part of */

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
            t('%s is %s', '<replica_set> is <health>'),
            new Link(
                $this->item->name,
                Links::replicaSet($this->item->namespace, $this->item->name),
                ['class' => 'subject']
            ),
            Html::tag('span', ['class' => 'replica-text'], $this->item->name)
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
        if ($this->item->ready_replicas !== $this->item->actual_replicas) {
            return Icons::REPLICASET_UNHEALTHY;
        } else if ($this->item->ready_replicas === $this->item->actual_replicas) {
            return Icons::REPLICASET_HEALTHY;
        } else if ($this->item->ready_replicas === 0 && $this->item->actual_replicas !== 0) {
            return Icons::REPLICASET_CRITICAL;
        } else {
            return Icons::REPLICASET_UNKNOWN;
        }
    }
}
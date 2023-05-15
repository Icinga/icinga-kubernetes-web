<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class DaemonSetListItem extends BaseListItem
{
    /** @var $item DaemonSet The associated list item */
    /** @var $list DaemonSetList The list where the item is part of */

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
            t('%s is %s', '<daemon_set> is <health>'),
            new Link(
                $this->item->name,
                Links::daemonSet($this->item->namespace, $this->item->name),
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
        if ($this->item->number_misscheduled !== 0) {
            return Icons::DAEMONSET_UNHEALTHY;
        } else if ($this->item->desired_number_scheduled === $this->item->current_number_scheduled) {
            return Icons::DAEMONSET_HEALTHY;
        } else {
            return Icons::DAEMONSET_UNKNOWN;
        }
    }
}

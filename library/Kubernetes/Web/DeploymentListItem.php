<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\DeploymentHealth;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Deployment;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class DeploymentListItem extends BaseListItem
{
    /** @var $item Deployment The associated list item */
    /** @var $list DeploymentList The list where the item is part of */

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
            t('%s is %s', '<deployment> is <health>'),
            new Link(
                $this->item->name,
                Links::deployment($this->item->namespace, $this->item->name),
                ['class' => 'subject']
            ),
            Html::tag('span', ['class' => 'replica-text'], $this->getReplicaHealth())
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

    protected function getReplicaHealth(): string
    {
        if ($this->item->ready_replicas !== $this->item->replicas &&
            $this->item->unavailable_replicas !== 0) {
            return DeploymentHealth::UNHEALTHY;
        } else if ($this->item->ready_replicas === $this->item->replicas &&
            $this->item->unavailable_replicas === 0) {
            return DeploymentHealth::HEALTHY;
        } else if ($this->item->ready_replicas === 0 && $this->item->replicas !== 0) {
            return DeploymentHealth::CRITICAL;
        } else {
            return DeploymentHealth::UNKNOWN;
        }
    }

    protected function getHealthIcon(): string
    {
        switch ($this->getReplicaHealth()) {
            case DeploymentHealth::HEALTHY:
               return Icons::DEPLOYMENT_HEALTHY;
            case DeploymentHealth::UNHEALTHY:
               return Icons::DEPLOYMENT_UNHEALTHY;
            case DeploymentHealth::CRITICAL:
               return Icons::DEPLOYMENT_CRITICAL;
            case DeploymentHealth::UNKNOWN:
               return Icons::DEPLOYMENT_UNKNOWN;
        }
    }
}
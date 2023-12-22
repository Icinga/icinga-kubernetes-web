<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\DeploymentHealth;
use Icinga\Module\Kubernetes\Common\Health;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Deployment;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Str;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class DeploymentListItem extends BaseListItem
{
    /** @var $item Deployment The associated list item */
    /** @var $list DeploymentList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $health = $this->getHealth();
        $visual->addHtml(new Icon(Health::icon($health), ['class' => ['health-' . $health]]));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            t('%s is %s', '<deployment> is <health>'),
            new Link($this->item->name, Links::deployment($this->item), ['class' => 'subject']),
            Html::tag('span', null, $this->getHealth())
        ));
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
        $header->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        $pods = new HtmlElement('div', new Attributes(['class' => 'pod-balls']));
        for ($i = 0; $i < $this->item->unavailable_replicas; $i++) {
            $pods->addHtml(new StateBall('critical', StateBall::SIZE_MEDIUM));
        }
        $pending = $this->item->desired_replicas - $this->item->unavailable_replicas - $this->item->available_replicas;
        for ($i = 0; $i < $pending; $i++) {
            $pods->addHtml(new StateBall('pending', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $this->item->available_replicas; $i++) {
            $pods->addHtml(new StateBall('ok', StateBall::SIZE_MEDIUM));
        }
        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new VerticalKeyValue(t('Pods'), $pods));
        $keyValue->addHtml(new VerticalKeyValue(t('Strategy'), ucfirst(Str::camel($this->item->strategy))));
        $keyValue->addHtml(new VerticalKeyValue(t('Min Ready Seconds'), $this->item->min_ready_seconds));
        $keyValue->addHtml(new VerticalKeyValue(t('Progress Deadline Seconds'), $this->item->min_ready_seconds));
        $keyValue->addHtml(new VerticalKeyValue(t('Namespace'), $this->item->namespace));
        $main->addHtml($keyValue);
    }

    protected function getHealth(): string
    {
        switch (true) {
            case $this->item->unavailable_replicas > 0:
                return Health::UNHEALTHY;
            case $this->item->available_replicas < $this->item->desired_replicas:
                return Health::DEGRADED;
            default:
                return Health::HEALTHY;
        }
    }
}

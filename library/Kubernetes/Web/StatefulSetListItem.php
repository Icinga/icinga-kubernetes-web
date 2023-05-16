<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\StatefulSet;
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
use LogicException;

class StatefulSetListItem extends BaseListItem
{
    /** @var $item StatefulSet The associated list item */
    /** @var $list StatefulSetList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $content = new Icon($this->getHealthIcon(), ['class' => ['health-' . $this->getState()]]);
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
            Html::tag('span', ['class' => 'statefulset-text'], $this->getState())
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

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $main->addHtml($keyValue);

        $desired = $this->item->desired_replicas;
        $unknown = $desired - $this->item->actual_replicas;
        $available = $this->item->available_replicas;
        $pending = $available - $this->item->ready_replicas;
        $critical = $desired - $unknown - $available - $pending;
        $pods = new HtmlElement('div', new Attributes(['class' => 'pod-balls']));
        for ($i = 0; $i < $critical; $i++) {
            $pods->addHtml(new StateBall('critical', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $pending; $i++) {
            $pods->addHtml(new StateBall('pending', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $unknown; $i++) {
            $pods->addHtml(new StateBall('unknown', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $available; $i++) {
            $pods->addHtml(new StateBall('ok', StateBall::SIZE_MEDIUM));
        }
        $keyValue->add(new VerticalKeyValue('Pods', $pods));
        $keyValue->add(new VerticalKeyValue('Service Name', $this->item->service_name));
        $keyValue->add(new VerticalKeyValue('Management Policy', ucfirst(Str::camel($this->item->pod_management_policy))));
        $keyValue->add(new VerticalKeyValue('Update Strategy', ucfirst(Str::camel($this->item->update_strategy))));
        $keyValue->add(new VerticalKeyValue('Min Ready Seconds', $this->item->min_ready_seconds));
        $keyValue->add(new VerticalKeyValue('Namespace', $this->item->namespace));
    }

    protected function getHealthIcon(): string
    {
        switch ($this->getState()) {
            case StatefulSet::STATE_HEALTHY:
                return Icons::STATEFULSET_HEALTHY;
            case StatefulSet::STATE_DEGRADED:
                return Icons::STATEFULSET_UNHEALTHY;
            case StatefulSet::STATE_UNHEALTHY:
                return Icons::STATEFULSET_CRITICAL;
            default:
                throw new LogicException();
        }
    }

    protected function getState(): string
    {
        switch (true) {
            case $this->item->available_replicas === 0:
                return StatefulSet::STATE_UNHEALTHY;
            case $this->item->available_replicas < $this->item->desired_replicas:
                return StatefulSet::STATE_DEGRADED;
            default:
                return StatefulSet::STATE_HEALTHY;
        }
    }
}

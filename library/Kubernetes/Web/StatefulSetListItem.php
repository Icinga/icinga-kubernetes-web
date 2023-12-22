<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Health;
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

class StatefulSetListItem extends BaseListItem
{
    /** @var $item StatefulSet The associated list item */
    /** @var $list StatefulSetList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $health = $this->getHealth();
        $visual->addHtml(new Icon(Health::icon($health), ['class' => ['health-' . $health]]));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            t('%s is %s', '<stateful_set> is <health>'),
            new Link($this->item->name, Links::statefulSet($this->item), ['class' => 'subject']),
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
        $keyValue->addHtml(new VerticalKeyValue(t('Pods'), $pods));
        $keyValue->addHtml(new VerticalKeyValue(t('Service Name'), $this->item->service_name));
        $keyValue->addHtml(new VerticalKeyValue(
            t('Management Policy'),
            ucfirst(Str::camel($this->item->pod_management_policy))
        ));
        $keyValue->addHtml(new VerticalKeyValue(
            t('Update Strategy'),
            ucfirst(Str::camel($this->item->update_strategy))
        ));
        $keyValue->addHtml(new VerticalKeyValue(t('Min Ready Seconds'), $this->item->min_ready_seconds));
        $keyValue->addHtml(new VerticalKeyValue(t('Namespace'), $this->item->namespace));
    }

    protected function getHealth(): string
    {
        switch (true) {
            case $this->item->available_replicas === 0:
                return Health::UNHEALTHY;
            case $this->item->available_replicas < $this->item->desired_replicas:
                return Health::DEGRADED;
            default:
                return Health::HEALTHY;
        }
    }
}

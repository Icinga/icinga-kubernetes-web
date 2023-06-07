<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Common\States;
use Icinga\Module\Kubernetes\Model\DaemonSet;
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

class DaemonSetListItem extends BaseListItem
{
    /** @var $item DaemonSet The associated list item */
    /** @var $list DaemonSetList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $health = $this->getHealth();
        $visual->addHtml(new Icon(States::icon($health), ['class' => ['health-' . $health]]));
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
            Html::tag('span', ['class' => 'statefulset-text'], $this->getHealth())
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

        $unknown = 0;
        $available = $this->item->number_available;
        $pending = 0;
        $critical = $this->item->number_unavailable;
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
        $keyValue->add(new VerticalKeyValue('Update Strategy', ucfirst(Str::camel($this->item->update_strategy))));
        $keyValue->add(new VerticalKeyValue('Min Ready Seconds', $this->item->min_ready_seconds));
        $keyValue->add(new VerticalKeyValue('Namespace', $this->item->namespace));
    }

    protected function getHealth(): string
    {
        if ($this->item->desired_number_scheduled < 1) {
            return States::UNDECIDABLE;
        }

        switch (true) {
            case $this->item->number_available < 1:
                return States::UNHEALTHY;
            case $this->item->number_unavailable > 1:
                return States::DEGRADED;
            default:
                return States::HEALTHY;
        }
    }
}
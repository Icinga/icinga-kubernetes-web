<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Widget\ItemCountIndicator;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;
use LogicException;

class StatefulSetListItem extends BaseListItem
{
    use Translation;

    const UPDATE_STRATEGY_ICONS = [
        'rollingupdate' => 'repeat',
        'recreate' => 'recycle'
    ];

    const MANAGEMENT_POLICY_ICONS = [
        'orderedready' => 'shuffle',
        'parallel' => 'right-left'
    ];

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
            [
                HtmlElement::create(
                    'span',
                    new Attributes(['class' => 'badge']),
                    [
                        new Icon('folder-open'),
                        new Text($this->item->namespace)
                    ]
                ),
                new Link(
                    $this->item->name,
                    Links::statefulSet($this->item),
                    ['class' => 'subject']
                )
            ],
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

        $main->add($this->createCaption());

        $main->add($this->createFooter());
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $desired = $this->item->desired_replicas;
        $unknown = $desired - $this->item->actual_replicas;
        $available = $this->item->available_replicas;
        $pending = $available - $this->item->ready_replicas;
        $critical = $desired - $unknown - $available - $pending;

        $pods = new ItemCountIndicator(null, 'hexagon');
        $podCount = 0;
        for ($i = 0; $i < $critical; $i++) {
            $pods->addItem('critical');
            $podCount++;
        }
        for ($i = 0; $i < $pending; $i++) {
            $pods->addItem('pending');
            $podCount++;
        }
        for ($i = 0; $i < $unknown; $i++) {
            $pods->addItem('unknown');
            $podCount++;
        }
        for ($i = 0; $i < $available; $i++) {
            $pods->addItem('ok');
            $podCount++;
        }
        $footer->add((
                new HorizontalKeyValue(new Icon('box'), $pods))->addAttributes(
                    [
                        'title' => $podCount . '(' . $critical + $unknown . ') ' . $this->translatePlural('Pod', 'Pods', $podCount),
                        'class'  => 'pods-value'
                    ]
            ));
        $footer->add((
                new Icon(new Icon(self::MANAGEMENT_POLICY_ICONS[strtolower(Str::camel($this->item->pod_management_policy))]))
            )->addAttributes(
            [
                'title' => 'Management Policy: ' . ucwords(str_replace('_', ' ', $this->item->pod_management_policy))
            ])
        );

        $footer->add((
            new Icon(self::UPDATE_STRATEGY_ICONS[strtolower(Str::camel($this->item->update_strategy))])
        )->addAttributes([
                'title' => 'Update Strategy: ' . ucwords(str_replace('_', ' ', ($this->item->update_strategy)))
            ]));
        $footer->add((new HorizontalKeyValue(new Icon('stopwatch'), $this->item->min_ready_seconds))->addAttributes(
            [
                'title' => 'Min Ready Seconds',
                'class' => 'push-right'
            ]
        ));

        $footer->add((new HorizontalKeyValue(new Icon('cogs'), $this->item->service_name))->addAttributes(
            [
                'title' => 'Service  Name',
            ]
        ));
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

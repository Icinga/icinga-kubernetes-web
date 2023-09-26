<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Common\States;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Widget\ItemCountIndicator;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class DaemonSetListItem extends BaseListItem
{
    const UPDATE_STRATEGY_ICONS = [
        'rollingupdate' => 'repeat',
        'recreate' => 'recycle'
    ];

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
                    Links::daemonSet($this->item),
                    ['class' => 'subject']
                )
            ],
            Html::tag('span', ['class' => 'statefulset-text'], $this->getHealth())
        );

        $title->addHtml($content);
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $unknown = 0;
        $available = $this->item->number_available;
        $pending = 0;
        $critical = $this->item->number_unavailable;

        $pods = new ItemCountIndicator(null, 'hexagon');
        for ($i = 0; $i < $critical; $i++) {
            $pods->addItem('critical');
        }
        for ($i = 0; $i < $pending; $i++) {
            $pods->addItem('pending');
        }
        for ($i = 0; $i < $unknown; $i++) {
            $pods->addItem('unknown');
        }
        for ($i = 0; $i < $available; $i++) {
            $pods->addItem('ok');
        }
        $footer->add((new HorizontalKeyValue(new Icon('box'), $pods))->addAttributes([
            'class' => 'pods-value'
        ]));

        $footer->add(
            (
                new Icon(self::UPDATE_STRATEGY_ICONS[strtolower(Str::camel($this->item->update_strategy))])
            )->addAttributes([
                'title' => 'Update Strategy: ' . ucwords(str_replace('_', ' ', ($this->item->update_strategy)))
            ])
        );


        $kvMinReadySecs = new HorizontalKeyValue(new Icon('stopwatch'), $this->item->min_ready_seconds);
        $kvMinReadySecs->addAttributes(['title' => 'Min Ready Seconds: ' . $this->item->min_ready_seconds]);
        $footer->add($kvMinReadySecs);
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

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $caption->add(new PluginOutputContainer(new PluginOutput('Some carefully chosen text that gives insights about the daemon set\'s condition')));
    }
}

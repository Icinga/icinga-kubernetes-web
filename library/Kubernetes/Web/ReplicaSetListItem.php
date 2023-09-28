<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Common\States;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Widget\ItemCountIndicator;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class ReplicaSetListItem extends BaseListItem
{
    /** @var $item ReplicaSet The associated list item */
    /** @var $list ReplicaSetList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $health = $this->getHealth();
        $visual->addHtml(new Icon(States::icon($health), ['class' => ['health-' . $health]]));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<replica_set> is <health>'),
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
                    Links::replicaSet($this->item),
                    ['class' => 'subject']
                )
            ],
            Html::tag('span', ['class' => 'replica-text'], $this->getHealth())
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

        $main->addHtml($this->createFooter());
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $desired = $this->item->desired_replicas;
        $unknown = 0;
        $available = $this->item->available_replicas;
        $pending = 0;
        $critical = $desired - $available;
        $pods = new ItemCountIndicator(null,  'hexagon');
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
        $footer->add((new HorizontalKeyValue(new Icon('box'), $pods))->addAttributes(
            ['class'  => 'pods-value']
        ));
        $footer->add(
            (new HorizontalKeyValue(new Icon('stopwatch'), $this->item->min_ready_seconds))
            ->addAttributes(['title' => 'Min Ready Seconds: ' . $this->item->min_ready_seconds])
        );
    }

    protected function getHealth(): string
    {
        if ($this->item->desired_replicas < 1) {
            return States::UNDECIDABLE;
        }

        switch (true) {
            case $this->item->available_replicas < 1:
                return States::UNHEALTHY;
            case $this->item->available_replicas < $this->item->desired_replicas:
                return States::DEGRADED;
            default:
                return States::HEALTHY;
        }
    }
}

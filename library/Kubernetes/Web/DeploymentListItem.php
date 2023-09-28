<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Common\States;
use Icinga\Module\Kubernetes\Model\Deployment;
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
use ipl\Web\Widget\TimeAgo;

class DeploymentListItem extends BaseListItem
{
    use Translation;

    const STRATEGY_ICONS = [
        'rollingupdate' => 'repeat',
        'recreate' => 'recycle'
    ];

    /** @var $item Deployment The associated list item */
    /** @var $list DeploymentList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $health = $this->getHealth();
        $visual->addHtml(new Icon(States::icon($health), ['class' => ['health-' . $health]]));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<deployment> is <health>'),
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
                    Links::deployment($this->item),
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

        $main->add($this->createFooter());
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $desired = $this->item->desired_replicas;
        $unknown = 0;
        $available = $this->item->available_replicas;
        $pending = 0;
        $critical = $this->item->unavailable_replicas;
        $pods = new ItemCountIndicator(null,  'hexagon');
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
        $footer->add((new HorizontalKeyValue(new Icon('box'), $pods))->addAttributes([
            'title' => $this->translatePlural('Pod', 'Pods', $podCount),
            'class' => 'pods-value'
        ]));
        $footer->add((
        new Icon(self::STRATEGY_ICONS[strtolower(Str::camel($this->item->strategy))])
        )->addAttributes([
                'title' => 'Update Strategy: ' . ucwords(str_replace('_', ' ', ($this->item->strategy)))
            ]));
        $footer->add(
            (new HorizontalKeyValue(new Icon('stopwatch'), $this->item->min_ready_seconds))
                ->addAttributes(['title' => 'Min Ready Seconds: ' . $this->item->min_ready_seconds])
        );

        $footer->add(
            (new HorizontalKeyValue(new Icon('skull-crossbones'), $this->item->min_ready_seconds))
                ->addAttributes(['title' => 'Progress Deadline Seconds: ' . $this->item->min_ready_seconds])
        );
    }

    protected function getHealth(): string
    {
        switch (true) {
            case $this->item->unavailable_replicas > 0:
                return States::UNHEALTHY;
            case $this->item->available_replicas < $this->item->desired_replicas:
                return States::DEGRADED;
            default:
                return States::HEALTHY;
        }
    }
}

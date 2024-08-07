<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Common\Metrics;
use Icinga\Module\Kubernetes\Model\Container;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class PodListItem extends BaseListItem
{
    use Translation;

    public const QOS_ICONS = [
        'BestEffort' => 'eraser',
        'Burstable'  => 'life-ring',
        'Guaranteed' => 'heart-pulse'
    ];

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml(
            $this->createTitle(),
            new TimeAgo($this->item->created->getTimestamp())
        );
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new Text($this->item->icinga_state_reason));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml(
            $this->createHeader(),
            $this->createCaption(),
            $this->createFooter()
        );
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $containerRestarts = 0;
        $containers = new ItemCountIndicator();
        $containers->setStyle($containers::STYLE_BOX);
        /** @var Container $container */
        foreach ($this->item->container as $container) {
            $containerRestarts += $container->restart_count;
            $containers->addIndicator($container->icinga_state, 1);
        }

        $footer->addHtml(
            (new HorizontalKeyValue(new Icon('box'), $containers))
                ->addAttributes([
                    'title' => sprintf(
                        $this->translate(
                            '%d %s running (%d not running)',
                            '%d:num_of_running_containers %s:containers_translation'
                            . ' (%d:num_of_not_running_containers)'
                        ),
                        $containers->getIndicator('ok'),
                        $this->translatePlural('container', 'containers', $containers->getIndicator('ok')),
                        $containers->getIndicator('critical')
                    )
                ]),
            new HorizontalKeyValue(
                new Icon('arrows-spin', ['title' => $this->translate('Container Restarts')]),
                $containerRestarts
            ),
            new HorizontalKeyValue(
                (new Icon('recycle'))->addAttributes(['title' => $this->translate('Restart Policy')]),
                $this->item->restart_policy
            ),
            new HorizontalKeyValue(
                (new Icon('life-ring'))->addAttributes(['title' => $this->translate('Quality of Service')]),
                $this->item->qos
            ),
            (new HorizontalKeyValue(
                $this->translate('IP'),
                $this->item->ip ?? $this->translate('None')
            ))
                ->addAttributes(['class' => 'push-left']),
            new HorizontalKeyValue(new Icon('share-nodes'), $this->item->node_name ?? $this->translate('None'))
        );

        $metrics = new Metrics(Database::connection());
        $podMetricsCurrent = $metrics->getPodMetricsCurrent(
            $this->item->uuid,
            Metrics::POD_CPU_REQUEST,
            Metrics::POD_CPU_LIMIT,
            Metrics::POD_CPU_USAGE_CORES,
            Metrics::POD_MEMORY_REQUEST,
            Metrics::POD_MEMORY_LIMIT,
            Metrics::POD_MEMORY_USAGE_BYTES
        );

        if (
            isset($podMetricsCurrent[Metrics::POD_CPU_LIMIT])
            && $podMetricsCurrent[Metrics::POD_CPU_REQUEST] < $podMetricsCurrent[Metrics::POD_CPU_LIMIT]
        ) {
            $footer->addHtml(
                new VerticalKeyValue(
                    $this->translate('CPU Request/Limit'),
                    new DoughnutChartRequestLimit(
                        'chart-mini',
                        $podMetricsCurrent[Metrics::POD_CPU_REQUEST],
                        $podMetricsCurrent[Metrics::POD_CPU_LIMIT],
                        $podMetricsCurrent[Metrics::POD_CPU_USAGE_CORES],
                        Metrics::COLOR_CPU
                    )
                )
            );
        }

        if (
            isset($podMetricsCurrent[Metrics::POD_MEMORY_LIMIT])
            && $podMetricsCurrent[Metrics::POD_MEMORY_REQUEST] < $podMetricsCurrent[Metrics::POD_MEMORY_LIMIT]
        ) {
            $footer->addHtml(
                new VerticalKeyValue(
                    $this->translate('Memory Request/Limit'),
                    new DoughnutChartRequestLimit(
                        'chart-mini',
                        $podMetricsCurrent[Metrics::POD_MEMORY_REQUEST],
                        $podMetricsCurrent[Metrics::POD_MEMORY_LIMIT],
                        $podMetricsCurrent[Metrics::POD_MEMORY_USAGE_BYTES],
                        Metrics::COLOR_MEMORY
                    )
                )
            );
        }
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<pod> is <icinga_state>'),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text($this->item->namespace)
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
                        new Text($this->item->name)
                    ),
                    Links::pod($this->item),
                    new Attributes(['class' => 'subject'])
                )
            ],
            new HtmlElement(
                'span', new Attributes(['class' => 'icinga-state-text']), new Text($this->item->icinga_state)
            )
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

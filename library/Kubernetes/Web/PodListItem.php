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
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class PodListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header
            ->addHtml($this->createTitle())
            ->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        $main->addHtml(new HtmlElement(
            'div',
            new Attributes(['class' => 'state-reason list']),
            Text::create(explode("\n", (string)$this->item->icinga_state_reason)[0])
        ));

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('IP'), $this->item->ip));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('QoS'), ucfirst(Str::camel($this->item->qos))));
        $containerRestarts = 0;
        $containers = new HtmlElement('span');
        /** @var Container $container */
        foreach ($this->item->container as $container) {
            $containerRestarts += $container->restart_count;
            $containers->addHtml(new StateBall($container->icinga_state, StateBall::SIZE_MEDIUM));
        }
        $keyValue->addHtml(new VerticalKeyValue($this->translate('Containers'), $containers));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('Restarts'), $containerRestarts));
        $keyValue->addHtml(new HtmlElement(
            'div',
            null,
            new HorizontalKeyValue($this->translate('Namespace'), $this->item->namespace),
            new HorizontalKeyValue($this->translate('Node'), $this->item->node_name)
        ));

        $metrics = new Metrics(Database::connection());
        $podMetricsCurrent = $metrics->getPodMetricsCurrent(
            $this->item->id,
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
            $keyValue->addHtml(
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
            $keyValue->addHtml(
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

        $main->addHtml($keyValue);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<pod> is <pod_phase>'),
            new Link($this->item->name, Links::pod($this->item), ['class' => 'subject']),
            Html::tag('span', null, $this->item->icinga_state)
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

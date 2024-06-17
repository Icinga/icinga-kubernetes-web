<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Common\Metrics;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\VerticalKeyValue;

class NodeListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        $main->addHtml(new HtmlElement(
            'div',
            new Attributes(['class' => 'state-reason list']),
            Text::create($this->item->icinga_state_reason)
        ));

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('CIDR'), $this->item->pod_cidr));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('Pod Capacity'), $this->item->pod_capacity));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('IPs Available'), $this->item->num_ips));
        $keyValue->addHtml(new VerticalKeyValue(
            $this->translate('CPU Capacity'),
            sprintf('%d cores', $this->item->cpu_allocatable / 1000)
        ));
        $keyValue->addHtml(new VerticalKeyValue(
            $this->translate('Memory Capacity'),
            Format::bytes($this->item->memory_allocatable / 1000)
        ));

        $metrics = new Metrics(Database::connection());
        $nodeMetrics = $metrics->getNodeMetricsCurrent(
            $this->item->uuid,
            Metrics::NODE_CPU_USAGE,
            Metrics::NODE_MEMORY_USAGE
        );

        if (isset($nodeMetrics[Metrics::NODE_CPU_USAGE])) {
            $keyValue->addHtml(new VerticalKeyValue(
                $this->translate('CPU Usage'),
                new DoughnutChartStates(
                    'chart-mini',
                    $nodeMetrics[Metrics::NODE_CPU_USAGE],
                    'CPU Usage',
                    implode(', ', [Metrics::COLOR_CPU, Metrics::COLOR_WARNING, Metrics::COLOR_CRITICAL])
                )
            ));
        }

        if (isset($nodeMetrics[Metrics::NODE_MEMORY_USAGE])) {
            $keyValue->addHtml(new VerticalKeyValue(
                $this->translate('Memory Usage'),
                new DoughnutChartStates(
                    'chart-mini',
                    $nodeMetrics[Metrics::NODE_MEMORY_USAGE],
                    'Memory Usage',
                    implode(', ', [Metrics::COLOR_MEMORY, Metrics::COLOR_WARNING, Metrics::COLOR_CRITICAL])
                )
            ));
        }

        $main->addHtml($keyValue);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<node> is <ready>'),
            new Link($this->item->name, Links::node($this->item), ['class' => 'subject']),
            Html::tag('span', null, $this->item->icinga_state)
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

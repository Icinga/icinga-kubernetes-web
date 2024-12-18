<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Metrics;
use ipl\Html\BaseHtmlElement;

class DetailMetricCharts extends BaseHtmlElement
{
    protected $metrics;

    protected $tag = 'section';

    public function __construct(array $metrics)
    {
        $this->metrics = $metrics;
    }

    protected function assemble(): void
    {
        $metricRow = [];
        if (isset($this->metrics[Metrics::NODE_CPU_USAGE])) {
            $metricRow[] = new LineChart(
                'chart-medium',
                implode(', ', $this->metrics[Metrics::NODE_CPU_USAGE]),
                implode(', ', array_keys($this->metrics[Metrics::NODE_CPU_USAGE])),
                'CPU Usage',
                Metrics::COLOR_CPU
            );
        }
        if (isset($this->metrics[Metrics::NODE_MEMORY_USAGE])) {
            $metricRow[] = new LineChart(
                'chart-medium',
                implode(', ', $this->metrics[Metrics::NODE_MEMORY_USAGE]),
                implode(', ', array_keys($this->metrics[Metrics::NODE_MEMORY_USAGE])),
                'Memory Usage',
                Metrics::COLOR_MEMORY
            );
        }

        $this->addHtml(new MetricCharts($metricRow));
    }
}

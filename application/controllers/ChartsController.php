<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use DateInterval;
use DateTime;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Metrics;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\DoughnutChart;
use Icinga\Module\Kubernetes\Web\DoughnutChartRequestLimit;
use Icinga\Module\Kubernetes\Web\DoughnutChartStates;
use Icinga\Module\Kubernetes\Web\LineChart;
use Icinga\Module\Kubernetes\Web\LineChartMinified;
use Icinga\Module\Kubernetes\Web\NavigationList;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class ChartsController extends Controller
{
    protected int $LAST_1_HOUR = 60 * 60 * 1000;
    protected int $LAST_12_HOURS = 60 * 60 * 12 * 1000;
    protected int $LAST_24_HOURS = 60 * 60 * 24 * 1000;

    public function indexAction(): void
    {
        $this->addContent(new NavigationList([
            ['href' => '/icingaweb2/kubernetes/charts/cluster', 'text' => 'Cluster Metrics'],
            ['href' => '/icingaweb2/kubernetes/charts/node', 'text' => 'Node Metrics'],
            ['href' => '/icingaweb2/kubernetes/charts/pod', 'text' => 'Pod Metrics'],
            ['href' => '/icingaweb2/kubernetes/charts/container', 'text' => 'Container Metrics']
        ]));
    }

    public function clusterAction(): void
    {
        $this->addTitleTab($this->translate('Cluster Metrics'));

        $this->addContent(
            new HtmlElement(
                'a',
                new Attributes(['href' => '/icingaweb2/kubernetes/charts']),
                new Text('Back to Charts')
            )
        );

        $metrics = new Metrics(Database::connection());

        $clusterMetrics = $metrics->getClusterMetrics(
            (new DateTime())->sub(new DateInterval('PT12H')),
            Metrics::CLUSTER_CPU_USAGE,
            Metrics::CLUSTER_MEMORY_USAGE
        );

        $this->addContent(
            new LineChart(
                'chart-medium',
                implode(', ', $clusterMetrics[Metrics::CLUSTER_CPU_USAGE]),
                implode(', ', array_keys($clusterMetrics[Metrics::CLUSTER_CPU_USAGE])),
                'CPU Usage',
                '#00a8ff'
            )
        );

        $this->addContent(
            new LineChart(
                'chart-medium',
                implode(', ', $clusterMetrics[Metrics::CLUSTER_MEMORY_USAGE]),
                implode(', ', array_keys($clusterMetrics[Metrics::CLUSTER_MEMORY_USAGE])),
                'Memory Usage',
                '#8c7ae6'
            )
        );

        $pods = $metrics->getNumberOfPodsByState(
            Metrics::POD_STATE_RUNNING,
            Metrics::POD_STATE_PENDING,
            Metrics::POD_STATE_FAILED,
            Metrics::POD_STATE_SUCCEEDED
        );

        $this->addContent(
            new DoughnutChart(
                'chart-small',
                implode(
                    ', ',
                    [
                        $pods[Metrics::POD_STATE_RUNNING],
                        $pods[Metrics::POD_STATE_PENDING],
                        $pods[Metrics::POD_STATE_FAILED],
                        $pods[Metrics::POD_STATE_SUCCEEDED]
                    ]
                ),
                implode(
                    ', ',
                    [
                        $pods[Metrics::POD_STATE_RUNNING] . ' Running',
                        $pods[Metrics::POD_STATE_PENDING] . ' Pending',
                        $pods[Metrics::POD_STATE_FAILED] . ' Failed',
                        $pods[Metrics::POD_STATE_SUCCEEDED] . ' Succeeded'
                    ]
                ),
                '#007bff, #ffc107, #dc3545, #28a745'
            )
        );

        $current = $metrics->getClusterMetrics(
            (new DateTime())->sub(new DateInterval('PT2M')),
            Metrics::CLUSTER_CPU_USAGE,
            Metrics::CLUSTER_MEMORY_USAGE
        );

        $this->addContent(
            new DoughnutChartStates(
                'chart-small',
                $current[Metrics::CLUSTER_CPU_USAGE][array_key_last($current[Metrics::CLUSTER_CPU_USAGE])],
                'CPU Usage',
                '#28a745, #ffc107, #dc3545'
            )
        );

        $this->addContent(
            new DoughnutChartStates(
                'chart-small',
                $current[Metrics::CLUSTER_MEMORY_USAGE][array_key_last($current[Metrics::CLUSTER_MEMORY_USAGE])],
                'Memory Usage',
                '#28a745, #ffc107, #dc3545'
            )
        );
    }

    public function nodeAction(): void
    {
        $this->addTitleTab($this->translate('Node Metrics'));

        $this->addContent(
            new HtmlElement(
                'a',
                new Attributes(['href' => '/icingaweb2/kubernetes/charts']),
                new Text('Back to Charts')
            )
        );

        $metrics = new Metrics(Database::connection());

        $nodeNetworkMetrics = $metrics->getNodesMetrics(
            (new DateTime())->sub(new DateInterval('PT1H')),
            Metrics::NODE_NETWORK_RECEIVED_BYTES,
            Metrics::NODE_NETWORK_TRANSMITTED_BYTES
        );


        foreach ($nodeNetworkMetrics as $node) {
            $this->addContent(
                new LineChart(
                    'chart-medium',
                    implode(', ', $node[Metrics::NODE_NETWORK_RECEIVED_BYTES])
                    . '; '
                    . implode(', ', $node[Metrics::NODE_NETWORK_TRANSMITTED_BYTES]),
                    implode(', ', array_keys($node[Metrics::NODE_NETWORK_RECEIVED_BYTES])),
                    'Received Bytes; Transmitted Bytes',
                    '#593684; #a3367f'
                )
            );
        }
    }

    public function podAction(): void
    {
        $this->addTitleTab($this->translate('Pod Metrics'));

        $this->addContent(
            new HtmlElement(
                'a',
                new Attributes(['href' => '/icingaweb2/kubernetes/charts']),
                new Text('Back to Charts')
            )
        );

        $metrics = new Metrics(Database::connection());

        $podMetricsCurrent = $metrics->getPodsMetricsCurrent(
            Metrics::POD_CPU_REQUEST,
            Metrics::POD_CPU_LIMIT,
            Metrics::POD_CPU_USAGE_CORES,
            Metrics::POD_MEMORY_REQUEST,
            Metrics::POD_MEMORY_LIMIT,
            Metrics::POD_MEMORY_USAGE_BYTES
        );

        $podMetricsPeriod = $metrics->getPodsMetrics(
            (new DateTime())->sub(new DateInterval('PT1H')),
            Metrics::POD_CPU_USAGE,
            Metrics::POD_MEMORY_USAGE
        );

        $podMetrics = Metrics::mergeMetrics($podMetricsCurrent, $podMetricsPeriod);

        $table = new HtmlElement('table', new Attributes(['class' => 'condition-table']));
        $table->addHtml(
            new HtmlElement(
                'thead',
                null,
                new HtmlElement(
                    'tr',
                    null,
                    new HtmlElement('th', null, new Text('Pod Name')),
                    new HtmlElement('th', null, new Text('Cpu')),
                    new HtmlElement('th', null, new Text('Memory')),
                    new HtmlElement('th', null, new Text('Cpu')),
                    new HtmlElement('th', null, new Text('Memory'))
                )
            )
        );

        $tbody = new HtmlElement('tbody');

        foreach ($podMetrics as $pod) {
            $tr = new HtmlElement('tr');
            $tr->addHtml(new HtmlElement('td', null, new Text($pod['name'])));
            $td = new HtmlElement('td', null);

            if (isset($pod[Metrics::POD_CPU_LIMIT]) && $pod[Metrics::POD_CPU_REQUEST] < $pod[Metrics::POD_CPU_LIMIT]) {
                $td->addHtml(
                    new DoughnutChartRequestLimit(
                        'chart-mini',
                        $pod[Metrics::POD_CPU_REQUEST],
                        $pod[Metrics::POD_CPU_LIMIT],
                        $pod[Metrics::POD_CPU_USAGE_CORES]
                    )
                );
            }

            $tr->addHtml($td);
            $td = new HtmlElement('td', null);

            if (
                isset($pod[Metrics::POD_MEMORY_LIMIT])
                && $pod[Metrics::POD_MEMORY_REQUEST] < $pod[Metrics::POD_MEMORY_LIMIT]
            ) {
                $td->addHtml(
                    new DoughnutChartRequestLimit(
                        'chart-mini',
                        $pod[Metrics::POD_MEMORY_REQUEST],
                        $pod[Metrics::POD_MEMORY_LIMIT],
                        $pod[Metrics::POD_MEMORY_USAGE_BYTES]
                    )
                );
            }

            $tr->addHtml($td);
            $td = new HtmlElement('td', null);

            if (isset($pod[Metrics::POD_CPU_USAGE])) {
                $td->addHtml(
                    new LineChartMinified(
                        'chart-mini',
                        implode(', ', $pod[Metrics::POD_CPU_USAGE]),
                        implode(', ', array_keys($pod[Metrics::POD_CPU_USAGE])),
                        '#00a8ff'
                    )
                );
            }

            $tr->addHtml($td);
            $td = new HtmlElement('td', null);

            if (isset($pod[Metrics::POD_MEMORY_USAGE])) {
                $td->addHtml(
                    new LineChartMinified(
                        'chart-mini',
                        implode(', ', $pod[Metrics::POD_MEMORY_USAGE]),
                        implode(', ', array_keys($pod[Metrics::POD_MEMORY_USAGE])),
                        '#ffa800'
                    )
                );
            }
            $tr->addHtml($td);
            $tbody->addHtml($tr);
        }

        $table->addHtml($tbody);
        $this->addContent($table);
    }

    public function containerAction(): void
    {
        $this->addTitleTab($this->translate('Container Metrics'));

        $this->addContent(
            new HtmlElement(
                'a',
                new Attributes(['href' => '/icingaweb2/kubernetes/charts']),
                new Text('Back to Charts')
            )
        );
    }
}

<?php

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\DoughnutChartStates;
use Icinga\Module\Kubernetes\Web\LineChartMinified;
use Icinga\Module\Kubernetes\Web\NavigationList;
use Icinga\Module\Kubernetes\Web\LineChart;
use Icinga\Module\Kubernetes\Web\DoughnutChart;
use Icinga\Module\Kubernetes\Web\DoughnutChartRequestLimit;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Metrics;
use ipl\Html\HtmlElement;
use ipl\Html\Attributes;
use ipl\Html\Text;
use DateTime;
use DateInterval;

class ChartsController extends Controller
{
    protected $LAST_1_HOUR = 60 * 60 * 1000;
    protected $LAST_12_HOURS = 60 * 60 * 12 * 1000;
    protected $LAST_24_HOURS = 60 * 60 * 24 * 1000;

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
        $clusterMetrics = [];

        $clusterMetrics = $metrics->getClusterUsage(
            (new DateTime())->sub(new DateInterval('PT12H')),
            Metrics::$ClusterCpuUsage,
            Metrics::$ClusterMemoryUsage
        );

        $this->addContent(
            new LineChart(
                'chart-medium',
                implode(', ', $clusterMetrics[Metrics::$ClusterCpuUsage]),
                implode(', ', array_keys($clusterMetrics[Metrics::$ClusterCpuUsage])),
                'CPU Usage',
                '#00a8ff'
            )
        );

        $this->addContent(
            new LineChart(
                'chart-medium',
                implode(', ', $clusterMetrics[Metrics::$ClusterMemoryUsage]),
                implode(', ', array_keys($clusterMetrics[Metrics::$ClusterMemoryUsage])),
                'Memory Usage',
                '#8c7ae6'
            )
        );

//        $numberOfRunningPods = $metrics->getNumberOfPodsByState('running');
//        $numberOfPendingPods = $metrics->getNumberOfPodsByState('pending');
//        $numberOfFailedPods = $metrics->getNumberOfPodsByState('failed');
//        $numberOfSucceededPods = $metrics->getNumberOfPodsByState('succeeded');

        $pods = $metrics->getNumberOfPodsByState(
            Metrics::$PodStateRunning,
            Metrics::$PodStatePending,
            Metrics::$PodStateFailed,
            Metrics::$PodStateSucceeded
        );

        $this->addContent(
            new DoughnutChart(
                'chart-small',
                implode(
                    ', ',
                    [
                    $pods[Metrics::$PodStateRunning],
                    $pods[Metrics::$PodStatePending],
                    $pods[Metrics::$PodStateFailed],
                    $pods[Metrics::$PodStateSucceeded]
                    ]
                ),
                implode(
                    ', ',
                    [
                        $pods[Metrics::$PodStateRunning] . ' Running',
                        $pods[Metrics::$PodStatePending] . ' Pending',
                        $pods[Metrics::$PodStateFailed] . ' Failed',
                        $pods[Metrics::$PodStateSucceeded] . ' Succeeded'
                    ]
                ),
                '#007bff, #ffc107, #dc3545, #28a745'
            )
        );

        $current = $metrics->getClusterUsage(
            (new DateTime())->sub(new DateInterval('PT1M')),
            Metrics::$ClusterCpuUsage,
            Metrics::$ClusterMemoryUsage
        );

        $this->addContent(
            new DoughnutChartStates(
                'chart-small',
                $current[Metrics::$ClusterCpuUsage][array_key_last($current[Metrics::$ClusterCpuUsage])],
                'CPU Usage',
                '#28a745, #ffc107, #dc3545'
            )
        );

        $this->addContent(
            new DoughnutChartStates(
                'chart-small',
                $current[Metrics::$ClusterMemoryUsage][array_key_last($current[Metrics::$ClusterMemoryUsage])],
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
        $nodeMetrics = [];

        $metrics->getNodeNetworkBytes($nodeMetrics, 'received', $this->LAST_1_HOUR);
        $metrics->getNodeNetworkBytes($nodeMetrics, 'transmitted', $this->LAST_1_HOUR);

        foreach ($nodeMetrics as $node) {
            $this->addContent(
                new LineChart(
                    'chart-medium',
                    implode(', ', $node['receivedBytes'])
                    . '; '
                    . implode(', ', $node['transmittedBytes']),
                    implode(', ', array_keys($node['receivedBytes'])),
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
        $podMetrics = [];

        $metrics->getPodRequest($podMetrics, 'cpu');
        $metrics->getPodRequest($podMetrics, 'memory');

        $metrics->getPodLimit($podMetrics, 'cpu');
        $metrics->getPodLimit($podMetrics, 'memory');

        $metrics->getPodCpuCoreUsage($podMetrics);
        $metrics->getPodMemoryByteUsage($podMetrics);

        $metrics->getPodUsage($podMetrics, 'cpu', $this->LAST_1_HOUR);
        $metrics->getPodUsage($podMetrics, 'memory', $this->LAST_1_HOUR);

        echo '<pre>';
//        print_r($podMetrics);
//        exit;

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
            ),
        );

        $tbody = new HtmlElement('tbody');

        foreach ($podMetrics as $pod) {
            $tr = new HtmlElement('tr');
            $tr->addHtml(new HtmlElement('td', null, new Text($pod['name'])));
            $td = new HtmlElement('td', null);

            if (isset($pod['cpuLimit']) && $pod['cpuRequest'] < $pod['cpuLimit']) {
                $td->addHtml(
                    new DoughnutChartRequestLimit(
                        'chart-mini',
                        $pod['cpuRequest'],
                        $pod['cpuLimit'],
                        $pod['cpuUsageCores']
                    )
                );
            }

            $tr->addHtml($td);
            $td = new HtmlElement('td', null);

            if (isset($pod['memoryLimit']) && $pod['memoryRequest'] < $pod['memoryLimit']) {
                $td->addHtml(
                    new DoughnutChartRequestLimit(
                        'chart-mini',
                        $pod['memoryRequest'],
                        $pod['memoryLimit'],
                        $pod['memoryUsageBytes']
                    )
                );
            }

            $tr->addHtml($td);
            $td = new HtmlElement('td', null);

            if (isset($pod['cpu'])) {
                $td->addHtml(
                    new LineChartMinified(
                        'chart-mini',
                        implode(', ', $pod['cpu']),
                        implode(', ', array_keys($pod['cpu'])),
                        '#00a8ff'
                    )
                );
            }

            $tr->addHtml($td);
            $td = new HtmlElement('td', null);

            if (isset($pod['memory'])) {
                $td->addHtml(
                    new LineChartMinified(
                        'chart-mini',
                        implode(', ', $pod['memory']),
                        implode(', ', array_keys($pod['memory'])),
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

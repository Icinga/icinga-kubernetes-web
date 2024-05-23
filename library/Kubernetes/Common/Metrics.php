<?php

namespace Icinga\Module\Kubernetes\Common;

use ipl\Sql\Select;
use ipl\Sql\Connection;
use PDO;
use DateTimeInterface;

class Metrics
{
    public static string $ClusterCpuUsage = 'cpu.usage';

    public static string $ClusterMemoryUsage = 'memory.usage';

    public static string $PodStateRunning = 'running';

    public static string $PodStatePending = 'pending';

    public static string $PodStateFailed = 'failed';

    public static string $PodStateSucceeded = 'succeeded';

    protected Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getClusterUsage(DateTimeInterface $startDateTime, string ...$metricNames): array
    {
        $out = [];

        foreach ($metricNames as $metricName) {
            $data = [];
            $dbData = $this->db->prepexec(
                (new Select())
                    ->columns(['timestamp', 'value'])
                    ->from('prometheus_cluster_metric')
                    ->where(
                        '`group` = ? AND timestamp > ?',
                        $metricName,
                        $startDateTime->getTimestamp() * 1000
                    )
            );

            foreach ($dbData->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $ts = $row['timestamp'];
                $data[$ts] = $row['value'];
            }

            $this->fillGaps($data);
            ksort($data);
            $out[$metricName] = $data;
        }

        return $out;
    }

    public function getNumberOfPodsByState(string ...$states): array
    {
        $out = [];

        foreach ($states as $state) {
            $dbData = $this->db->prepexec(
                (new Select())
                    ->columns(['value'])
                    ->from('prometheus_cluster_metric')
                    ->where('`group` = ?', "pod.$state")
                    ->orderBy('timestamp DESC')
                    ->limit(1)
            );

            $out[$state] = $dbData->fetchAll(PDO::FETCH_ASSOC)[0]['value'];
        }

        return $out;
    }

//    public function getNumberOfPodsByState(string $state): int
//    {
//        $dbData = $this->db->prepexec(
//            (new Select())
//                ->columns(['value'])
//                ->from('prometheus_cluster_metric')
//                ->where('`group` = ?', "pod.$state")
//                ->orderBy('timestamp DESC')
//                ->limit(1)
//        );
//
//        return $dbData->fetchAll(PDO::FETCH_ASSOC)[0]['value'];
//    }

    public function getClusterUsageCurrent(string $resource): float|null
    {
        $dbData = $this->db->prepexec(
            (new Select())
                ->columns(['value'])
                ->from('prometheus_cluster_metric')
                ->where(
                    '`group` = ? AND timestamp > UNIX_TIMESTAMP() * 1000 - ?',
                    "$resource.usage",
                    2 * 60 * 1000
                )
                ->orderBy('timestamp DESC')
                ->limit(1)
        );

        return $dbData->fetchAll(PDO::FETCH_ASSOC)[0]['value'];
    }

    public function getNodeNetworkBytes(array &$nodeMetrics, string $direction, int $period): void
    {
        $dbData = $this->db->prepexec(
            (new Select())
                ->columns(['node.id', 'node.name', 'node_metric.timestamp', 'node_metric.value'])
                ->from('prometheus_node_metric AS node_metric')
                ->join('node', 'node_metric.node_id = node.id')
                ->where(
                    'node_metric.group = ? AND node_metric.timestamp > UNIX_TIMESTAMP() * 1000 - ?',
                    "network.$direction.bytes",
                    $period
                )
        );

        foreach ($dbData->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $ts = $row['timestamp'];
            $nodeMetrics[$id]['name'] = $row['name'];
            $nodeMetrics[$id][$direction . 'Bytes'][$ts] = $row['value'];
        }

        foreach ($nodeMetrics as &$nodeMetric) {
            $this->fillGaps($nodeMetric[$direction . 'Bytes']);
            ksort($nodeMetric[$direction . 'Bytes']);
        }
    }

    public function getPodRequest(array &$podMetrics, string $resource): void
    {
        $dbData = $this->db->prepexec(
            (new Select())
                ->columns(['pod.id', 'pod.name', 'pod_metric.value'])
                ->from('prometheus_pod_metric AS pod_metric')
                ->join('pod', 'pod_metric.pod_id = pod.id')
                ->join(
                    [
                        'latest_metrics' => (new Select())
                            ->columns(['pod_id', 'MAX(timestamp) AS latest_timestamp'])
                            ->from('prometheus_pod_metric')
                            ->where('`group` = ?', "$resource.request")
                            ->groupBy('pod_id')
                    ],
                    'pod_metric.pod_id = latest_metrics.pod_id'
                    . ' AND pod_metric.timestamp = latest_metrics.latest_timestamp'
                )
                ->where('pod_metric.group = ?', "$resource.request")
        );

        foreach ($dbData->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $podMetrics[$id]['name'] = $row['name'];
            $podMetrics[$id][$resource . 'Request'] = $row['value'];
        }
    }

    public function getPodLimit(array &$podMetrics, string $resource): void
    {
        $dbData = $this->db->prepexec(
            (new Select())
                ->columns(['pod.id', 'pod.name', 'pod_metric.value'])
                ->from('prometheus_pod_metric AS pod_metric')
                ->join('pod', 'pod_metric.pod_id = pod.id')
                ->join(
                    [
                        'latest_metrics' => (new Select())
                            ->columns(['pod_id', 'MAX(timestamp) AS latest_timestamp'])
                            ->from('prometheus_pod_metric')
                            ->where('`group` = ?', "$resource.limit")
                            ->groupBy('pod_id')
                    ],
                    'pod_metric.pod_id = latest_metrics.pod_id'
                    . ' AND pod_metric.timestamp = latest_metrics.latest_timestamp'
                )
                ->where('pod_metric.group = ?', "$resource.limit")
        );

        foreach ($dbData->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $podMetrics[$id]['name'] = $row['name'];
            $podMetrics[$id][$resource . 'Limit'] = $row['value'];
        }
    }

    public function getPodCpuCoreUsage(array &$podMetrics): void
    {
        $dbData = $this->db->prepexec(
            (new Select())
                ->columns(['pod.id', 'pod.name', 'pod_metric.value'])
                ->from('prometheus_pod_metric AS pod_metric')
                ->join('pod', 'pod_metric.pod_id = pod.id')
                ->join(
                    [
                        'latest_metrics' => (new Select())
                            ->columns(['pod_id', 'MAX(timestamp) AS latest_timestamp'])
                            ->from('prometheus_pod_metric')
                            ->where('`group` = ?', 'cpu.usage.cores')
                            ->groupBy('pod_id')
                    ],
                    'pod_metric.pod_id = latest_metrics.pod_id'
                    . ' AND pod_metric.timestamp = latest_metrics.latest_timestamp'
                )
                ->where('pod_metric.group = ?', 'cpu.usage.cores')
                ->where('timestamp > UNIX_TIMESTAMP() * 1000 - ?', 2 * 60 * 1000)
        );

        foreach ($dbData->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $podMetrics[$id]['name'] = $row['name'];
            $podMetrics[$id]['cpuUsageCores'] = $row['value'];
        }
    }

    public function getPodMemoryByteUsage(array &$podMetrics): void
    {
        $dbData = $this->db->prepexec(
            (new Select())
                ->columns(['pod.id', 'pod.name', 'pod_metric.value'])
                ->from('prometheus_pod_metric AS pod_metric')
                ->join('pod', 'pod_metric.pod_id = pod.id')
                ->join(
                    [
                        'latest_metrics' => (new Select())
                            ->columns(['pod_id', 'MAX(timestamp) AS latest_timestamp'])
                            ->from('prometheus_pod_metric')
                            ->where('`group` = ?', 'memory.usage.bytes')
                            ->groupBy('pod_id')
                    ],
                    'pod_metric.pod_id = latest_metrics.pod_id'
                    . ' AND pod_metric.timestamp = latest_metrics.latest_timestamp'
                )
                ->where('pod_metric.group = ?', 'memory.usage.bytes')
                ->where('timestamp > UNIX_TIMESTAMP() * 1000 - ?', 2 * 60 * 1000)
        );

        foreach ($dbData->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $podMetrics[$id]['name'] = $row['name'];
            $podMetrics[$id]['memoryUsageBytes'] = $row['value'];
        }
    }

    public function getPodUsage(array &$podMetrics, string $resource, int $period): void
    {
        $dbData = $this->db->prepexec(
            (new Select())
                ->columns(['pod.id', 'pod.name', 'pod_metric.timestamp', 'pod_metric.value'])
                ->from('prometheus_pod_metric AS pod_metric')
                ->join('pod', 'pod_metric.pod_id = pod.id')
                ->where(
                    'pod_metric.group = ? AND pod_metric.timestamp > UNIX_TIMESTAMP() * 1000 - ?',
                    "$resource.usage",
                    $period
                )
        );

        foreach ($dbData->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $ts = $row['timestamp'];

            $podMetrics[$id]['name'] = $row['name'];
            $podMetrics[$id][$resource][$ts] = $row['value'];
        }

        foreach ($podMetrics as &$podMetric) {
            if (!isset($podMetric[$resource])) {
                continue;
            }
            $this->fillGaps($podMetric[$resource]);
            ksort($podMetric[$resource]);
        }
    }

    protected function fillGaps(array &$data): void
    {
        $firstTs = min(array_keys($data));
        $lastTs = max(array_keys($data));

        for ($ts = $firstTs; $ts <= $lastTs; $ts += 60000) {
            if (!isset($data[$ts])) {
                $data[$ts] = 0;
            }
        }
    }
}

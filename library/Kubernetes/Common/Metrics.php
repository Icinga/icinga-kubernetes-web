<?php

namespace Icinga\Module\Kubernetes\Common;

use ipl\Sql\Select;
use ipl\Sql\Connection;
use PDO;
use DateTimeInterface;

class Metrics
{
    public const CLUSTER_CPU_USAGE = 'cpu.usage';

    public const CLUSTER_MEMORY_USAGE = 'memory.usage';

    public const POD_STATE_RUNNING = 'running';

    public const POD_STATE_PENDING = 'pending';

    public const POD_STATE_FAILED = 'failed';

    public const POD_STATE_SUCCEEDED = 'succeeded';

    public const NODE_NETWORK_RECEIVED_BYTES = 'network.received.bytes';

    public const NODE_NETWORK_TRANSMITTED_BYTES = 'network.transmitted.bytes';

    public const POD_CPU_REQUEST = 'cpu.request';

    public const POD_MEMORY_REQUEST = 'memory.request';

    public const POD_CPU_LIMIT = 'cpu.limit';

    public const POD_MEMORY_LIMIT = 'memory.limit';

    public const POD_CPU_USAGE_CORES = 'cpu.usage.cores';

    public const POD_CPU_USAGE = 'cpu.usage';

    public const POD_MEMORY_USAGE_BYTES = 'memory.usage.bytes';

    public const POD_MEMORY_USAGE = 'memory.usage';


    protected Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getClusterMetrics(DateTimeInterface $startDateTime, string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['timestamp', 'value'])
                    ->from('prometheus_cluster_metric')
                    ->where(
                        'category = ? AND timestamp > ?',
                        $category,
                        $startDateTime->getTimestamp() * 1000
                    ),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $ts = $row['timestamp'];
                $data[$category][$ts] = $row['value'];
            }

            $this->fillGaps($data[$category]);
            ksort($data[$category]);
        }

        return $data;
    }

    public function getNumberOfPodsByState(string ...$states): array
    {
        $out = [];

        foreach ($states as $state) {
            $dbData = $this->db->prepexec(
                (new Select())
                    ->columns(['value'])
                    ->from('prometheus_cluster_metric')
                    ->where('category = ?', "pod.$state")
                    ->orderBy('timestamp DESC')
                    ->limit(1)
            );

            $out[$state] = $dbData->fetchAll(PDO::FETCH_ASSOC)[0]['value'];
        }

        return $out;
    }

    public function getNodeNetworkBytes(DateTimeInterface $startDataTime, string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['node.id', 'node.name', 'node_metric.timestamp', 'node_metric.value'])
                    ->from('prometheus_node_metric AS node_metric')
                    ->join('node', 'node_metric.node_id = node.id')
                    ->where(
                        'node_metric.category = ? AND node_metric.timestamp > ?',
                        $category,
                        $startDataTime->getTimestamp() * 1000
                    ),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $id = $row['id'];
                $ts = $row['timestamp'];
                $data[$id]['name'] = $row['name'];
                $data[$id][$category][$ts] = $row['value'];
            }

            foreach ($data as &$pod) {
                $this->fillGaps($pod[$category]);
                ksort($pod[$category]);
            }
        }

        return $data;
    }

    public function getPodMetricsCurrent(string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['pod.id', 'pod.name', 'pod_metric.value'])
                    ->from('prometheus_pod_metric AS pod_metric')
                    ->join('pod', 'pod_metric.pod_id = pod.id')
                    ->join(
                        [
                            'latest_metrics' => (new Select())
                                ->columns(['pod_id', 'MAX(timestamp) AS latest_timestamp'])
                                ->from('prometheus_pod_metric')
                                ->where('category = ?', $category)
                                ->groupBy('pod_id')
                        ],
                        'pod_metric.pod_id = latest_metrics.pod_id'
                        . ' AND pod_metric.timestamp = latest_metrics.latest_timestamp'
                    )
                    ->where('pod_metric.category = ?', $category),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $id = $row['id'];
                $data[$id]['name'] = $row['name'];
                $data[$id][$category] = $row['value'];
            }
        }

        return $data;
    }

    public function getPodMetrics(DateTimeInterface $startDateTime, string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['pod.id', 'pod.name', 'pod_metric.timestamp', 'pod_metric.value'])
                    ->from('prometheus_pod_metric AS pod_metric')
                    ->join('pod', 'pod_metric.pod_id = pod.id')
                    ->where(
                        'pod_metric.category = ? AND pod_metric.timestamp > ?',
                        $category,
                        $startDateTime->getTimestamp() * 1000
                    ),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $id = $row['id'];
                $ts = $row['timestamp'];

                $data[$id]['name'] = $row['name'];
                $data[$id][$category][$ts] = $row['value'];
            }

            foreach ($data as &$pod) {
                if (!isset($pod[$category])) {
                    continue;
                }
                $this->fillGaps($pod[$category]);
                ksort($pod[$category]);
            }
        }

        return $data;
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
    public static function mergeMetrics(array ...$arrays): array
    {
        $merged = [];

        array_walk($arrays, function ($array) use (&$merged) {
            array_walk($array, function ($item, $key) use (&$merged) {
                if (isset($merged[$key])) {
                    $merged[$key] += $item;
                } else {
                    $merged[$key] = $item;
                }
            });
        });

        return $merged;
    }
}

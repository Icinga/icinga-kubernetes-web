<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

use DateTimeInterface;
use ipl\Sql\Connection;
use ipl\Sql\Select;
use PDO;

class Metrics
{
    public const COLOR_CPU = '#1982c4';

    public const COLOR_MEMORY = '#6a4c93';

    public const COLOR_WARNING = '#ffaa44';

    public const COLOR_CRITICAL = '#ff5566';

    public const CLUSTER_CPU_USAGE = 'cpu.usage';

    public const CLUSTER_MEMORY_USAGE = 'memory.usage';

    public const POD_STATE_RUNNING = 'running';

    public const POD_STATE_PENDING = 'pending';

    public const POD_STATE_FAILED = 'failed';

    public const POD_STATE_SUCCEEDED = 'succeeded';

    public const NODE_CPU_USAGE = 'cpu.usage';

    public const NODE_MEMORY_USAGE = 'memory.usage';

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

            if (! isset($data[$category])) {
                continue;
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

    public function getNodesMetrics(DateTimeInterface $startDataTime, string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['node.uuid', 'node.name', 'node_metric.timestamp', 'node_metric.value'])
                    ->from('prometheus_node_metric AS node_metric')
                    ->join('node', 'node_metric.node_uuid = node.uuid')
                    ->where(
                        'node_metric.category = ? AND node_metric.timestamp > ?',
                        $category,
                        $startDataTime->getTimestamp() * 1000
                    ),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $id = $row['uuid'];
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

    public function getNodeMetrics(DateTimeInterface $startDataTime, string $nodeId, string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['node_metric.timestamp', 'node_metric.value'])
                    ->from('prometheus_node_metric AS node_metric')
                    ->join('node', 'node_metric.node_uuid = node.uuid')
                    ->where(
                        'node_uuid = ? AND node_metric.category = ? AND node_metric.timestamp > ?',
                        $nodeId,
                        $category,
                        $startDataTime->getTimestamp() * 1000
                    ),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $data[$category][$row['timestamp']] = $row['value'];
            }

            if (! isset($data[$category])) {
                continue;
            }

            $this->fillGaps($data[$category]);
            ksort($data[$category]);
        }

        return $data;
    }

    public function getNodeMetricsCurrent(string $nodeId, string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['node_metric.value'])
                    ->from('prometheus_node_metric AS node_metric')
                    ->join('node', 'node_metric.node_uuid = node.uuid')
                    ->join(
                        [
                            'latest_metrics' => (new Select())
                                ->columns(['MAX(timestamp) AS latest_timestamp'])
                                ->from('prometheus_node_metric')
                                ->where('node_uuid = ? AND category = ?', $nodeId, $category)
                        ],
                        'node_metric.timestamp = latest_metrics.latest_timestamp'
                    )
                    ->where('node_uuid = ? AND node_metric.category = ?', $nodeId, $category),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $data[$category] = $row['value'];
            }
        }

        return $data;
    }

    public function getPodsMetricsCurrent(string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['pod.uuid', 'pod.name', 'pod_metric.value'])
                    ->from('prometheus_pod_metric AS pod_metric')
                    ->join('pod', 'pod_metric.pod_uuid = pod.uuid')
                    ->join(
                        [
                            'latest_metrics' => (new Select())
                                ->columns(['pod_uuid', 'MAX(timestamp) AS latest_timestamp'])
                                ->from('prometheus_pod_metric')
                                ->where('category = ?', $category)
                                ->groupBy('pod_uuid')
                        ],
                        'pod_metric.pod_uuid = latest_metrics.pod_uuid'
                        . ' AND pod_metric.timestamp = latest_metrics.latest_timestamp'
                    )
                    ->where('pod_metric.category = ?', $category),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $id = $row['uuid'];
                $data[$id]['name'] = $row['name'];
                $data[$id][$category] = $row['value'];
            }
        }

        return $data;
    }

    public function getPodMetricsCurrent(string $podId, string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['pod_metric.value'])
                    ->from('prometheus_pod_metric AS pod_metric')
                    ->join('pod', 'pod_metric.pod_uuid = pod.uuid')
                    ->join(
                        [
                            'latest_metrics' => (new Select())
                                ->columns(['MAX(timestamp) AS latest_timestamp'])
                                ->from('prometheus_pod_metric')
                                ->where('pod_uuid = ? AND category = ?', $podId, $category)
                        ],
                        'pod_metric.timestamp = latest_metrics.latest_timestamp'
                    )
                    ->where('pod_uuid = ? AND pod_metric.category = ?', $podId, $category),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $data[$category] = $row['value'];
            }
        }

        return $data;
    }

    public function getPodsMetrics(DateTimeInterface $startDateTime, string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['pod.uuid', 'pod.name', 'pod_metric.timestamp', 'pod_metric.value'])
                    ->from('prometheus_pod_metric AS pod_metric')
                    ->join('pod', 'pod_metric.pod_uuid = pod.uuid')
                    ->where(
                        'pod_metric.category = ? AND pod_metric.timestamp > ?',
                        $category,
                        $startDateTime->getTimestamp() * 1000
                    ),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $id = $row['uuid'];
                $ts = $row['timestamp'];

                $data[$id]['name'] = $row['name'];
                $data[$id][$category][$ts] = $row['value'];
            }

            foreach ($data as &$pod) {
                if (! isset($pod[$category])) {
                    continue;
                }
                $this->fillGaps($pod[$category]);
                ksort($pod[$category]);
            }
        }

        return $data;
    }

    public function getPodMetrics(DateTimeInterface $startDateTime, string $podId, string ...$metricCategories): array
    {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['pod_metric.timestamp', 'pod_metric.value'])
                    ->from('prometheus_pod_metric AS pod_metric')
                    ->join('pod', 'pod_metric.pod_uuid = pod.uuid')
                    ->where(
                        'pod.uuid = ? AND pod_metric.category = ? AND pod_metric.timestamp > ?',
                        $podId,
                        $category,
                        $startDateTime->getTimestamp() * 1000
                    ),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $data[$category][$row['timestamp']] = $row['value'];
            }

            if (! isset($data[$category])) {
                continue;
            }

            $this->fillGaps($data[$category]);
            ksort($data[$category]);
        }

        return $data;
    }

    public function getReplicaSetMetrics(
        DateTimeInterface $startDateTime,
        string $replicaSetId,
        string ...$metricCategories
    ): array {
        $data = [];

        foreach ($metricCategories as $category) {
            $rs = $this->db->YieldAll(
                (new Select())
                    ->columns(['pm.timestamp', 'SUM(pm.value) AS value'])
                    ->from('prometheus_pod_metric AS pm')
                    ->join('pod AS p', 'pm.pod_uuid = p.uuid')
                    ->join('pod_owner AS po', 'p.uuid = po.pod_uuid')
                    ->join('replica_set AS rs', 'po.owner_uuid = rs.uuid')
                    ->where(
                        'rs.uuid = ? AND pm.category = ? AND pm.timestamp > ?',
                        $replicaSetId,
                        $category,
                        $startDateTime->getTimestamp() * 1000
                    )
                    ->groupBy("pm.timestamp"),
                PDO::FETCH_ASSOC
            );

            foreach ($rs as $row) {
                $data[$category][$row['timestamp']] = $row['value'];
            }

            if (! isset($data[$category])) {
                continue;
            }

            $this->fillGaps($data[$category]);
            ksort($data[$category]);
        }

        return $data;
    }

    public function getDaemonSetMetrics(
        DateTimeInterface $startDateTime,
        string $daemonSetId,
        string ...$metricCategories
    ): array {
        $data = [];

        foreach ($metricCategories as $category) {
            $ds = $this->db->YieldAll(
                (new Select())
                    ->columns(['pm.timestamp', 'SUM(pm.value) AS value'])
                    ->from('prometheus_pod_metric AS pm')
                    ->join('pod AS p', 'pm.pod_uuid = p.uuid')
                    ->join('pod_owner AS po', 'p.uuid = po.pod_uuid')
                    ->join('daemon_set AS ds', 'po.owner_uuid = ds.uuid')
                    ->where(
                        'ds.uuid = ? AND pm.category = ? AND pm.timestamp > ?',
                        $daemonSetId,
                        $category,
                        $startDateTime->getTimestamp() * 1000
                    )
                    ->groupBy("pm.timestamp"),
                PDO::FETCH_ASSOC
            );

            foreach ($ds as $row) {
                $data[$category][$row['timestamp']] = $row['value'];
            }

            if (! isset($data[$category])) {
                continue;
            }

            $this->fillGaps($data[$category]);
            ksort($data[$category]);
        }

        return $data;
    }

    public function getStatefulSetMetrics(
        DateTimeInterface $startDateTime,
        string $statefulSetId,
        string ...$metricCategories
    ): array {
        $data = [];

        foreach ($metricCategories as $category) {
            $ss = $this->db->YieldAll(
                (new Select())
                    ->columns(['pm.timestamp', 'SUM(pm.value) AS value'])
                    ->from('prometheus_pod_metric AS pm')
                    ->join('pod AS p', 'pm.pod_uuid = p.uuid')
                    ->join('pod_owner AS po', 'p.uuid = po.pod_uuid')
                    ->join('stateful_set AS ss', 'po.owner_uuid = ss.uuid')
                    ->where(
                        'ss.uuid = ? AND pm.category = ? AND pm.timestamp > ?',
                        $statefulSetId,
                        $category,
                        $startDateTime->getTimestamp() * 1000
                    )
                    ->groupBy("pm.timestamp"),
                PDO::FETCH_ASSOC
            );

            foreach ($ss as $row) {
                $data[$category][$row['timestamp']] = $row['value'];
            }

            if (! isset($data[$category])) {
                continue;
            }

            $this->fillGaps($data[$category]);
            ksort($data[$category]);
        }

        return $data;
    }

    public function getDeploymentMetrics(
        DateTimeInterface $startDateTime,
        string $deploymentId,
        string ...$metricCategories
    ): array {
        $data = [];

        foreach ($metricCategories as $category) {
            $depl = $this->db->YieldAll(
                (new Select())
                    ->columns(['pm.timestamp', 'SUM(pm.value) AS value'])
                    ->from('prometheus_pod_metric AS pm')
                    ->join('pod AS p', 'pm.pod_uuid = p.uuid')
                    ->join('pod_owner AS po', 'p.uuid = po.pod_uuid')
                    ->join('replica_set AS rs', 'rs.uuid = po.owner_uuid')
                    ->join('replica_set_owner AS rso', 'rs.uuid = rso.replica_set_uuid')
                    ->join('deployment AS d', 'd.uuid = rso.owner_uuid')
                    ->where(
                        'd.uuid = ? AND pm.category = ? AND pm.timestamp > ?',
                        $deploymentId,
                        $category,
                        $startDateTime->getTimestamp() * 1000
                    )
                    ->groupBy("pm.timestamp"),
                PDO::FETCH_ASSOC
            );

            foreach ($depl as $row) {
                $data[$category][$row['timestamp']] = $row['value'];
            }

            if (! isset($data[$category])) {
                continue;
            }

            $this->fillGaps($data[$category]);
            ksort($data[$category]);
        }

        return $data;
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

    protected function fillGaps(array &$data): void
    {
        $firstTs = min(array_keys($data));
        $lastTs = max(array_keys($data));

        for ($ts = $firstTs; $ts <= $lastTs; $ts += 60000) {
            if (! isset($data[$ts])) {
                $data[$ts] = 0;
            }
        }
    }
}

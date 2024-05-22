<?php

namespace Icinga\Module\Kubernetes\Web;

use PDO;

class MetricQuery
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = new PDO('mysql:host=mysql;dbname=kubernetes', 'kubernetes', 'kubernetes');
    }

    public function getGlobalUsage(string $resource, int $period): array
    {
        $stmt = $this->db->prepare("
    SELECT timestamp, value FROM prometheus_cluster_metric 
        WHERE `group` = '$resource.usage' AND timestamp > ((UNIX_TIMESTAMP() * 1000) - $period)
    ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ts = $row['timestamp'];
            $data[$ts] = $row['value'];
        }

        $this->fillGaps($data);
        ksort($data);

        return $data;
    }

    public function getNumberOfPodsByState(string $state): int
    {
        $stmt = $this->db->prepare("
    SELECT value FROM prometheus_cluster_metric
        WHERE `group` = 'pod.$state' ORDER BY timestamp DESC LIMIT 1
    ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['value'];
    }

    public function getNodeNetworkBytes(array &$nodeMetrics, string $direction, int $period): void
    {
        $stmt = $this->db->prepare("
    SELECT node.id, node.name, node_metric.timestamp, node_metric.value 
    FROM prometheus_node_metric AS node_metric
    INNER JOIN node ON node_metric.node_id = node.id
        WHERE 
            node_metric.group = 'network.$direction.bytes' 
          AND 
            node_metric.timestamp > ((UNIX_TIMESTAMP() * 1000) - $period)
    ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
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

    public function getClusterUsage(string $resource): float|null
    {
        $stmt = $this->db->prepare("
    SELECT value FROM prometheus_cluster_metric
        WHERE 
            `group` = '$resource.usage' 
          AND 
            timestamp > ((UNIX_TIMESTAMP() * 1000) - 2 * 60 * 1000) 
        ORDER BY timestamp DESC LIMIT 1
    ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['value'];
    }

    public function getPodRequest(array &$podMetrics, string $resource): void
    {
        $stmt = $this->db->prepare("
    SELECT pod.id,
       pod.name,
       pod_metric.value
    FROM prometheus_pod_metric AS pod_metric
         INNER JOIN pod ON pod_metric.pod_id = pod.id
         INNER JOIN (
            SELECT pod_id, MAX(timestamp) AS latest_timestamp
            FROM prometheus_pod_metric
            WHERE `group` = '$resource.request'
            GROUP BY pod_id
        ) AS latest_metrics 
             ON 
                 pod_metric.pod_id = latest_metrics.pod_id 
                     AND 
                 pod_metric.timestamp = latest_metrics.latest_timestamp
    WHERE pod_metric.group = '$resource.request';
    ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $podMetrics[$id]['name'] = $row['name'];
            $podMetrics[$id][$resource . 'Request'] = $row['value'];
        }
    }

    public function getPodLimit(array &$podMetrics, string $resource): void
    {
        $stmt = $this->db->prepare("
    SELECT pod.id,
       pod.name,
       pod_metric.value
    FROM prometheus_pod_metric AS pod_metric
         INNER JOIN pod ON pod_metric.pod_id = pod.id
         INNER JOIN (
            SELECT pod_id, MAX(timestamp) AS latest_timestamp
            FROM prometheus_pod_metric
            WHERE `group` = '$resource.limit'
            GROUP BY pod_id
        ) AS latest_metrics 
             ON 
                 pod_metric.pod_id = latest_metrics.pod_id 
                     AND 
                 pod_metric.timestamp = latest_metrics.latest_timestamp
    WHERE pod_metric.group = '$resource.limit';
    ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $podMetrics[$id]['name'] = $row['name'];
            $podMetrics[$id][$resource . 'Limit'] = $row['value'];
        }
    }

    public function getPodCpuCoreUsage(array &$podMetrics): void
    {
        $stmt = $this->db->prepare("
    SELECT pod.id,
       pod.name,
       pod_metric.value
    FROM prometheus_pod_metric AS pod_metric
         INNER JOIN pod ON pod_metric.pod_id = pod.id
         INNER JOIN (
            SELECT pod_id, MAX(timestamp) AS latest_timestamp
            FROM prometheus_pod_metric
            WHERE `group` = 'cpu.usage.cores'
            GROUP BY pod_id
        ) AS latest_metrics 
             ON 
                 pod_metric.pod_id = latest_metrics.pod_id 
                     AND 
                 pod_metric.timestamp = latest_metrics.latest_timestamp
    WHERE 
        pod_metric.group = 'cpu.usage.cores'
    AND 
        timestamp > ((UNIX_TIMESTAMP() * 1000) - 2 * 60 * 1000) ;
");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $podMetrics[$id]['name'] = $row['name'];
            $podMetrics[$id]['cpuUsageCores'] = $row['value'];
        }
    }

    public function getPodMemoryByteUsage(array &$podMetrics): void
    {
        $stmt = $this->db->prepare("
        SELECT pod.id,
           pod.name,
           pod_metric.value
        FROM prometheus_pod_metric AS pod_metric
             INNER JOIN pod ON pod_metric.pod_id = pod.id
             INNER JOIN (
                SELECT pod_id, MAX(timestamp) AS latest_timestamp
                FROM prometheus_pod_metric
                WHERE `group` = 'memory.usage.bytes'
                GROUP BY pod_id
            ) AS latest_metrics 
                 ON 
                     pod_metric.pod_id = latest_metrics.pod_id 
                         AND 
                     pod_metric.timestamp = latest_metrics.latest_timestamp
        WHERE 
            pod_metric.group = 'memory.usage.bytes'
        AND 
            timestamp > ((UNIX_TIMESTAMP() * 1000) - 2 * 60 * 1000);
        ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $podMetrics[$id]['name'] = $row['name'];
            $podMetrics[$id]['memoryUsageBytes'] = $row['value'];
        }
    }

    public function getPodUsage(array &$podMetrics, string $resource, int $period): void
    {
        $stmt = $this->db->prepare("
        SELECT pod.id,
           pod.name,
           pod_metric.timestamp,
           pod_metric.value
        FROM prometheus_pod_metric AS pod_metric
        INNER JOIN pod ON pod_metric.pod_id = pod.id
        WHERE 
            pod_metric.group = '$resource.usage'
        AND 
            timestamp > ((UNIX_TIMESTAMP() * 1000) - $period) 
        ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
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
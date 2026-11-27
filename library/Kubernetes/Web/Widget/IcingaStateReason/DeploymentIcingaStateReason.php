<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Annotation;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Condition;

class DeploymentIcingaStateReason extends WorkloadIcingaStateReason
{
    /**
     * Annotation in which Kubernetes stores the rollout revision of a Deployment and its ReplicaSets
     *
     * @var string
     */
    protected const REVISION_ANNOTATION = 'deployment.kubernetes.io/revision';

    protected function assemble(): void
    {
        [$kind, $namespaceSlashName, $reason] = explode(' ', $this->icingaStateReason, 3);
        $workloadName = explode('/', $namespaceSlashName)[1];

        $this->addHtml(new IcingaStateReasonRow(
            $this->icingaState,
            $kind,
            $workloadName,
            $reason,
            $this->buildTooltip($kind, $namespaceSlashName),
        ));

        $rs = $this->fetchCurrentReplicaSet();
        if ($rs === null) {
            return;
        }

        $this->addHtml(new HtmlElement(
            'div',
            new Attributes(['class' => 'workload-rows']),
            new WorkloadIcingaStateReason($rs->uuid, $rs->icinga_state_reason, $rs->icinga_state, $workloadName)
        ));
    }

    /**
     * Fetch the ReplicaSet of the current Deployment revision
     *
     * A Deployment owns several ReplicaSets during a rollout and keeps old ones for rollbacks. Kubernetes gives the
     * ReplicaSet of each new rollout, including a rollback that reuses an older ReplicaSet, the next revision, so the
     * current one has the highest revision. A ReplicaSet can also have rows of its earlier revisions, which are
     * always lower.
     *
     * @return ?ReplicaSet Null if no owned ReplicaSet with a revision is synchronized
     */
    protected function fetchCurrentReplicaSet(): ?ReplicaSet
    {
        $db = Database::connection();

        $replicaSets = [];
        foreach (ReplicaSet::on($db)->filter(Filter::equal('owner.owner_uuid', $this->uuid)) as $replicaSet) {
            $replicaSets[$replicaSet->uuid] = $replicaSet;
        }
        if ($replicaSets === []) {
            return null;
        }

        $revisions = Annotation::on($db)
            ->filter(Filter::all(
                Filter::any(...array_map(
                    fn(string $uuid): Condition => Filter::equal('resource_uuid', $uuid),
                    array_keys($replicaSets),
                )),
                Filter::equal('name', static::REVISION_ANNOTATION),
            ));

        $current = null;
        $currentRevision = 0;
        foreach ($revisions as $revision) {
            if ((int) $revision->value > $currentRevision) {
                $current = $replicaSets[$revision->resource_uuid];
                $currentRevision = (int) $revision->value;
            }
        }

        return $current;
    }
}

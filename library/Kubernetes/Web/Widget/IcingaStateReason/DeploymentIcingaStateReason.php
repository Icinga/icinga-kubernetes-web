<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;

class DeploymentIcingaStateReason extends WorkloadIcingaStateReason
{
    protected function assemble(): void
    {
        $rs = ReplicaSet::on(Database::connection())->filter(Filter::equal('owner.owner_uuid', $this->uuid))->first();

        [$kind, $namespaceSlashName, $reason] = explode(' ', $this->icingaStateReason, 3);
        $workloadName = explode('/', $namespaceSlashName)[1];

        $this->addHtml(new IcingaStateReasonRow(
            $this->icingaState,
            $kind,
            $workloadName,
            $reason,
            $this->buildTooltip($kind, $namespaceSlashName),
        ));

        $this->addHtml(new HtmlElement(
            'div',
            new Attributes(['class' => 'workload-rows']),
            new WorkloadIcingaStateReason($rs->uuid, $rs->icinga_state_reason, $rs->icinga_state, $workloadName)
        ));
    }
}

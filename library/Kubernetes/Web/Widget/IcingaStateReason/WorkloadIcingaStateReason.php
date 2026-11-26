<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Pod;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;

class WorkloadIcingaStateReason extends PodIcingaStateReason
{
    protected function assemble(): void
    {
        $pods = Pod::on(Database::connection())->filter(Filter::equal('owner.owner_uuid', $this->uuid));

        [$kind, $namespaceSlashName, $reason] = explode(' ', $this->icingaStateReason, 3);
        $workloadName = explode('/', $namespaceSlashName)[1];
        $podNamePrefix = $workloadName;
        if (in_array($kind, ['ReplicaSet', 'DaemonSet'], true)) {
            // Kubernetes limits generated Pod names to 63 characters, including a five-character suffix.
            $podNamePrefix = substr($workloadName, 0, 58);
        }

        $this->addHtml(new IcingaStateReasonRow(
            $this->icingaState,
            $kind,
            explode('/', $namespaceSlashName)[1],
            $reason,
            $this->buildTooltip($kind, $namespaceSlashName),
            $this->parentName
        ));

        $podRows = new HtmlElement('div', new Attributes(['class' => 'pod-rows']));

        foreach ($pods as $pod) {
            $parentName = strlen($pod->name) === 63 && str_starts_with($pod->name, $podNamePrefix)
                ? $podNamePrefix
                : $workloadName;

            $podRows->addHtml(new PodIcingaStateReason(
                $pod->uuid,
                $pod->icinga_state_reason,
                $pod->icinga_state,
                $parentName
            ));
        }
        $this->addHtml($podRows);
    }
}

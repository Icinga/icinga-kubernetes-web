<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class ClusterManagementDashboard extends Dashboard
{
    public function getTitle(): string
    {
        return $this->translate('Cluster Management');
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new KubernetesPhaseDashlet(
                'namespace',
                $this->translate('Namespaces'),
                $this->translate(
                    'Out of {total} total Namespaces, {Active} are Active, {Terminating} are Terminating.'
                )
            ),
            new IcingaStateDashlet(
                'node',
                $this->translate('Nodes'),
                $this->translate(
                    'Out of {total} total Nodes, {ok} are in OK state, {critical} are Critical, {warning} are 
                    in Warning state, and {unknown} are Unknown.'
                )
            )
        );
    }
}

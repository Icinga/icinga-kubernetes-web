<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class ObservabilityDashboard extends Dashboard
{
    public function getTitle(): string
    {
        return $this->translate('Observability');
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Dashlet(
                'event',
                $this->translate('Events'),
                $this->translate('Record changes and issues within the cluster.')
            )
        );
    }
}

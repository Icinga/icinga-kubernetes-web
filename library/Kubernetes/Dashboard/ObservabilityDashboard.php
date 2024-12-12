<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class ObservabilityDashboard extends Dashboard
{
    protected $dashletNames = [
        'Event',
        // TODO: Metrics
    ];

    public function getTitle()
    {
        return $this->translate('Observability');
    }
}

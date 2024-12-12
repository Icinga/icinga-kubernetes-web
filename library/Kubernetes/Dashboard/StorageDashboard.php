<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class StorageDashboard extends Dashboard
{
    protected $dashletNames = [
        'PersistentVolume',
        'PersistentVolumeClaim'
    ];

    public function getTitle()
    {
        return $this->translate('Storage');
    }
}

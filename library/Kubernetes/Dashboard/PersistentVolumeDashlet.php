<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use ipl\Stdlib\Filter;

class PersistentVolumeDashlet extends Dashlet
{
    protected $icon = 'icon kicon-persistent-volume';

    public function getTitle()
    {
        return $this->translate('Persistent Volumes');
    }

    public function getSummary()
    {
        return $this->translate(
            sprintf(
                'Out of %s total persistent volumes, %s are Bound, %s are Available, %s are Pending, %s are 
                Released, %s are Failed',
                $this->getPhaseCount(),
                $this->getPhaseCount('Bound'),
                $this->getPhaseCount('Available'),
                $this->getPhaseCount('Pending'),
                $this->getPhaseCount('Released'),
                $this->getPhaseCount('Failed')
            )
        );
    }

    private function getPhaseCount(string $phase = '')
    {
        return $phase === ''
            ? PersistentVolume::on(Database::connection())->count()
            : PersistentVolume::on(Database::connection())->filter(Filter::equal('phase', $phase))->count();
    }

    public function getUrl()
    {
        return 'kubernetes/persistent-volumes';
    }
}

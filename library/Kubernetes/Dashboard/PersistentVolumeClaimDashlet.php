<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use ipl\Stdlib\Filter;

class PersistentVolumeClaimDashlet extends Dashlet
{
    protected $icon = 'icon kicon-pvc';

    public function getTitle()
    {
        return $this->translate('Persistent Volume Claims');
    }

    public function getSummary()
    {
        return $this->translate(
            sprintf(
                'Out of %s total persistent volume claims, %s are Bound, %s are Pending, %s are Lost',
                $this->getPhaseCount(),
                $this->getPhaseCount('Bound'),
                $this->getPhaseCount('Pending'),
                $this->getPhaseCount('Lost')
            )
        );
    }

    private function getPhaseCount(string $phase = '')
    {
        return $phase === ''
            ? PersistentVolumeClaim::on(Database::connection())->count()
            : PersistentVolumeClaim::on(Database::connection())->filter(Filter::equal('phase', $phase))->count();
    }

    public function getUrl()
    {
        return 'kubernetes/persistent-volume-claims';
    }
}

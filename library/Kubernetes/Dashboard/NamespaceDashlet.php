<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use ipl\Stdlib\Filter;

class NamespaceDashlet extends Dashlet
{
    protected $icon = 'icon kicon-namespace';

    public function getTitle()
    {
        return $this->translate('Namespaces');
    }

    public function getSummary()
    {
        return $this->translate(
            sprintf(
                'Out of %s total namespaces, %s are Active, %s are Terminating',
                $this->getPhaseCount(),
                $this->getPhaseCount('Active'),
                $this->getPhaseCount('Terminating')
            )
        );
    }

    private function getPhaseCount(string $phase = '')
    {
        return $phase === ''
            ? NamespaceModel::on(Database::connection())->count()
            : NamespaceModel::on(Database::connection())->filter(Filter::equal('phase', $phase))->count();
    }

    public function getUrl()
    {
        return 'kubernetes/namespaces';
    }
}

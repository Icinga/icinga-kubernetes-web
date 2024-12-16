<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Web\Factory;
use ipl\Sql\Expression;

class IcingaStateDashlet extends Dashlet
{
    protected function getIcingaStateCounts(): array
    {
        $q = (Factory::createModel($this->kind)::on(Database::connection()))
            ->columns([
                'icinga_state',
                'count' => new Expression('COUNT(*)')
            ]);

        $q->getSelectBase()->groupBy('icinga_state');

        $counts = array_fill_keys(
            [
                'ok',
                'warning',
                'critical',
                'unknown',
                'pending'
            ],
            0
        );

        foreach ($q as $count) {
            $counts[$count->icinga_state] = $count->count;
        }

        $counts['total'] = array_sum($counts);

        return array_combine(
            array_map(fn($key) => '{' . $key . '}', array_keys($counts)),
            $counts
        );
    }

    protected function beforeAssemble(): void
    {
        $this->summary->addArgs($this->getIcingaStateCounts());
    }
}

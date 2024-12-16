<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Web\Factory;
use ipl\Sql\Expression;

class KubernetesPhaseDashlet extends Dashlet
{
    protected function getKubernetesPhaseCounts(): array
    {
        $q = (Factory::createModel($this->kind)::on(Database::connection()))
            ->columns([
                'phase',
                'count' => new Expression('COUNT(*)')
            ]);

        $q->getSelectBase()->groupBy('phase');

        $counts = array_fill_keys(
            [
                'Bound',
                'Failed',
                'Lost',
                'Released',
                'Available',
                'Active',
                'Terminating',
                'Pending',
            ],
            0
        );

        foreach ($q as $count) {
            $counts[$count->phase] = $count->count;
        }

        $counts['total'] = array_sum($counts);

        return array_combine(
            array_map(fn($key) => '{' . $key . '}', array_keys($counts)),
            $counts
        );
    }

    protected function beforeAssemble(): void
    {
        $this->summary->addArgs($this->getKubernetesPhaseCounts());
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Web\ListController;
use Icinga\Module\Kubernetes\Web\PodList;
use ipl\Orm\Query;

class PodsController extends ListController
{
    protected function getContentClass(): string
    {
        return PodList::class;
    }

    protected function getQuery(): Query
    {
        return Pod::on(Database::connection())->with('node');
    }

    protected function getSortColumns(): array
    {
        return [
            'pod.created desc' => $this->translate('Created'),
            'pod.name'         => $this->translate('Name'),
            'pod.namespace'    => $this->translate('Namespace'),
            'pod.phase'        => $this->translate('Phase'),
            'pod.node_name'    => $this->translate('Node')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Pods');
    }
}

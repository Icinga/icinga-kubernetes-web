<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class JobsController extends ListController
{
    protected function getQuery(): Query
    {
        return Job::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'job.created desc' => $this->translate('Created'),
            'job.name'         => $this->translate('Name'),
            'job.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Jobs');
    }

    protected function getPermission(): string
    {
        return Auth::SHOW_JOBS;
    }
}

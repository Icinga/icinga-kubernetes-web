<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Web\CronJobList;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class CronjobsController extends ListController
{
    protected function getContentClass(): string
    {
        return CronJobList::class;
    }

    protected function getQuery(): Query
    {
        return CronJob::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'cron_job.created desc' => $this->translate('Created'),
            'cron_job.name'         => $this->translate('Name'),
            'cron_job.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Cron Jobs');
    }
}

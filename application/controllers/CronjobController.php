<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\CronJobDetail;
use ipl\Stdlib\Filter;

class CronjobController extends Controller
{
    public function indexAction(): void
    {
        $namespace = $this->params->get('namespace');
        $name = $this->params->get('name');
        $id = $this->params->getRequired('id');

        $this->addTitleTab("Cron Job $namespace/$name");

        $cronJob = CronJob::on(Database::connection())
            ->filter(Filter::equal('id', $id))
            ->first();

        $this->addContent(new CronJobDetail($cronJob));
    }
}

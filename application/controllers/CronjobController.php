<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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
        $this->addTitleTab($this->translate('Cron Job'));

        /** @var CronJob $cronJob */
        $cronJob = CronJob::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($cronJob === null) {
            $this->httpNotFound($this->translate('Cron Job not found'));
        }

        $this->addContent(new CronJobDetail($cronJob));
    }
}

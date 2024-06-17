<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\CronJobDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class CronjobController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Cron Job'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var CronJob $cronJob */
        $cronJob = CronJob::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($cronJob === null) {
            $this->httpNotFound($this->translate('Cron Job not found'));
        }

        $this->addContent(new CronJobDetail($cronJob));
    }
}

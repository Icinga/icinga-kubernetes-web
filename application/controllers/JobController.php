<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\JobDetail;
use Icinga\Module\Kubernetes\Web\JobList;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class JobController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_JOBS);

        $this->addTitleTab($this->translate('Job'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $job = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_JOBS, Job::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($job === null) {
            $this->httpNotFound($this->translate('Job not found'));
        }

        $this->addControl((new JobList([$job]))->setActionList(false));

        $this->addContent(new JobDetail($job));
    }
}

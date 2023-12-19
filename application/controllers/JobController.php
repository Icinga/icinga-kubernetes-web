<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Web\JobDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class JobController extends CompatController
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Job'));

        /** @var Job $job */
        $job = Job::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($job === null) {
            $this->httpNotFound($this->translate('Job not found'));
        }

        $this->addContent(new JobDetail($job));
    }
}

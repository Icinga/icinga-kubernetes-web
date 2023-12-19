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
        $namespace = $this->params->get('namespace');
        $name = $this->params->get('name');
        $id = $this->params->getRequired('id');

        $this->addTitleTab("Job $namespace/$name");

        $job = Job::on(Database::connection())
            ->filter(Filter::equal('id', $id))
            ->first();

        $this->addContent(new JobDetail($job));
    }
}

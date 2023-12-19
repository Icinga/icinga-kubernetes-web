<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\ServiceDetail;
use ipl\Stdlib\Filter;

class ServiceController extends Controller
{
    public function indexAction(): void
    {
        $id = $this->params->getRequired('id');

        $query = Service::on(Database::connection())
            ->filter(Filter::equal('service.id', $id));

        /** @var Service $service */
        $service = $query->first();
        if ($service === null) {
            $this->httpNotFound($this->translate('Service not found'));
        }

        $this->addTitleTab("Service $service->name");

        $this->addContent(new ServiceDetail($service));
    }
}

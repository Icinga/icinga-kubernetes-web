<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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
        $this->addTitleTab($this->translate('Service'));

        /** @var Service $service */
        $service = Service::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($service === null) {
            $this->httpNotFound($this->translate('Service not found'));
        }

        $this->addContent(new ServiceDetail($service));
    }
}

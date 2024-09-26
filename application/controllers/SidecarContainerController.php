<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\SidecarContainer;
use Icinga\Module\Kubernetes\Web\ContainerDetail;
use Icinga\Module\Kubernetes\Web\Controller;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class SidecarContainerController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Sidecar Container'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var SidecarContainer $sidecarContainer */
        $sidecarContainer = SidecarContainer::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($sidecarContainer === null) {
            $this->httpNotFound($this->translate('Sidecar container not found'));
        }

        $this->addContent(new ContainerDetail($sidecarContainer));
    }
}

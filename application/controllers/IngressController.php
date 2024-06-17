<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\IngressDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class IngressController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Ingress'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var Ingress $ingress */
        $ingress = Ingress::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($ingress === null) {
            $this->httpNotFound($this->translate('Ingress not found'));
        }

        $this->addContent(new IngressDetail($ingress));
    }
}

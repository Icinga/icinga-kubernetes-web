<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\IngressDetail;
use ipl\Stdlib\Filter;

class IngressController extends Controller
{
    public function indexAction(): void
    {
        $id = $this->params->getRequired('id');

        $query = Ingress::on(Database::connection())
            ->filter(Filter::equal('ingress.id', $id));

        /** @var Ingress $ingress */
        $ingress = $query->first();
        if ($ingress === null) {
            $this->httpNotFound($this->translate('Ingress not found'));
        }

        $this->addTitleTab("Ingress $ingress->name");

        $this->addContent(new IngressDetail($ingress));
    }
}

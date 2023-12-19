<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Secret;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\SecretDetail;
use ipl\Stdlib\Filter;

class SecretController extends Controller
{
    public function indexAction(): void
    {
        $namespace = $this->params->get('namespace');
        $name = $this->params->get('name');
        $id = $this->params->getRequired('id');

        $this->addTitleTab("Secret $namespace/$name");

        $secret = Secret::on(Database::connection())
            ->filter(Filter::equal('id', $id))
            ->first();

        $this->addContent(new SecretDetail($secret));
    }

    protected function getPageSize($default)
    {
        return parent::getPageSize($default ?? 50);
    }
}

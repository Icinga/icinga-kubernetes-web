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
        $this->addTitleTab($this->translate('Secret'));

        /** @var Secret $secret */
        $secret = Secret::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($secret === null) {
            $this->httpNotFound($this->translate('Secret not found'));
        }

        $this->addContent(new SecretDetail($secret));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Secret;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Detail\SecretDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class SecretController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_SECRETS);

        $this->addTitleTab($this->translate('Secret'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $secret = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_SECRETS, Secret::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($secret === null) {
            $this->httpNotFound($this->translate('Secret not found'));
        }

        $this->addContent(new SecretDetail($secret));
    }
}

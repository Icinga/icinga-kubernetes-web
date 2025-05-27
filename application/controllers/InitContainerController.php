<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\InitContainer;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Detail\InitContainerDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class InitContainerController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Init Container'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var InitContainer $initcontainer */
        $initContainer = InitContainer::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($initContainer === null) {
            $this->httpNotFound($this->translate('Init container not found'));
        }

        $this->addContent(new InitContainerDetail($initContainer));
    }
}

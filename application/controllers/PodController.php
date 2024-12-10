<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\PodDetail;
use Icinga\Module\Kubernetes\Web\PodList;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class PodController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_PODS);

        $this->addTitleTab($this->translate('Pod'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $pod = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_PODS, Pod::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($pod === null) {
            $this->httpNotFound($this->translate('Pod not found'));
        }

        $this->addControl((new PodList([$pod]))->setActionList(false));

        $this->addContent(new PodDetail($pod));
    }
}

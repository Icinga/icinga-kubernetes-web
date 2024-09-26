<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\PersistentVolumeClaimDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class PersistentvolumeclaimController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Persistent Volume Claim'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var PersistentVolumeClaim $pvc */
        $pvc = PersistentVolumeClaim::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($pvc === null) {
            $this->httpNotFound($this->translate('Persistent Volume Claim not found'));
        }

        $this->addContent(new PersistentVolumeClaimDetail($pvc));
    }
}

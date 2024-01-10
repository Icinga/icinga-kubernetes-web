<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Web\PersistentVolumeClaimDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class PersistentvolumeclaimController extends CompatController
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Persistent Volume Claim'));

        /** @var PersistentVolumeClaim $pvc */
        $pvc = PersistentVolumeClaim::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($pvc === null) {
            $this->httpNotFound($this->translate('Persistent Volume Claim not found'));
        }

        $this->addContent(new PersistentVolumeClaimDetail($pvc));
    }
}

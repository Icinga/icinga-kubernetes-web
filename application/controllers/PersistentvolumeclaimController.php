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
        $id = $this->params->getRequired('id');

        $query = PersistentVolumeClaim::on(Database::connection())
            ->filter(Filter::all(
                Filter::equal('pvc.id', $id),
            ));

        /** @var PersistentVolumeClaim $pvc */
        $pvc = $query->first();
        if ($pvc === null) {
            $this->httpNotFound($this->translate('Persistent Volume Claim not found'));
        }

        $this->addTitleTab("Persistent Volume Claim $pvc->namespace/$pvc->name");

        $this->addContent(new PersistentVolumeClaimDetail($pvc));
    }
}

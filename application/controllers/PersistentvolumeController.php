<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Web\PersistentVolumeDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
use Ramsey\Uuid\Uuid;

class PersistentvolumeController extends CompatController
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Persistent Volume'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var PersistentVolume $persistentVolume */
        $persistentVolume = PersistentVolume::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($persistentVolume === null) {
            $this->httpNotFound($this->translate('Persistent Volume not found'));
        }

        $this->addContent(new PersistentVolumeDetail($persistentVolume));
    }
}

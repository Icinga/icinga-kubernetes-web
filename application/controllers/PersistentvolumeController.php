<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\PersistentVolumeDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class PersistentvolumeController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_PERSISTENT_VOLUMES);

        $this->addTitleTab($this->translate('Persistent Volume'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $persistentVolume = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_PERSISTENT_VOLUMES, PersistentVolume::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($persistentVolume === null) {
            $this->httpNotFound($this->translate('Persistent Volume not found'));
        }

        $this->addContent(new PersistentVolumeDetail($persistentVolume));
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Web\PersistentVolumeDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class PersistentvolumeController extends CompatController
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Persistent Volume'));

        /** @var PersistentVolume $persistentVolume */
        $persistentVolume = PersistentVolume::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($persistentVolume === null) {
            $this->httpNotFound($this->translate('Persistent Volume not found'));
        }

        $this->addContent(new PersistentVolumeDetail($persistentVolume));
    }
}

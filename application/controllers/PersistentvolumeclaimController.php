<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Controls\QuickActions;
use Icinga\Module\Kubernetes\Web\Detail\PersistentVolumeClaimDetail;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class PersistentvolumeclaimController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_PERSISTENT_VOLUME_CLAIMS);

        $this->addTitleTab($this->translate('Persistent Volume Claim'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $pvc = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_PERSISTENT_VOLUME_CLAIMS, PersistentVolumeClaim::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        $favorite = Favorite::on(Database::connection())
            ->filter(
                Filter::all(
                    Filter::equal('resource_uuid', $uuidBytes),
                    Filter::equal('username', Auth::getInstance()->getUser()->getUsername())
                )
            )
            ->first();

        if ($pvc === null) {
            $this->httpNotFound($this->translate('Persistent Volume Claim not found'));
        }

        $this->addControl(
            (new ResourceList([$pvc]))
                ->setDetailActionsDisabled()
                ->setViewMode(ViewMode::Minimal)
        );

        $this->addControl(new QuickActions($pvc, $favorite));

        $this->addContent(new PersistentVolumeClaimDetail($pvc));
    }
}

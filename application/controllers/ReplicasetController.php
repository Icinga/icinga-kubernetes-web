<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Controls\QuickActions;
use Icinga\Module\Kubernetes\Web\Detail\ReplicaSetDetail;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ReplicasetController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_REPLICA_SETS);

        $this->addTitleTab($this->translate('Replica Set'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $replicaSet = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_REPLICA_SETS, ReplicaSet::on(Database::connection()))
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

        if ($replicaSet === null) {
            $this->httpNotFound($this->translate('Replica Set not found'));
        }

        $this->addControl(
            (new ResourceList([$replicaSet]))
                ->setDetailActionsDisabled()
                ->setViewMode(ViewMode::Minimal)
        );

        $this->addControl(new QuickActions($replicaSet, $favorite));

        $this->addContent(new ReplicaSetDetail($replicaSet));
    }
}

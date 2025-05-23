<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Controls\QuickActions;
use Icinga\Module\Kubernetes\Web\Detail\IngressDetail;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class IngressController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_INGRESSES);

        $this->addTitleTab($this->translate('Ingress'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $ingress = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_INGRESSES, Ingress::on(Database::connection()))
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

        if ($ingress === null) {
            $this->httpNotFound($this->translate('Ingress not found'));
        }

        $this->addControl(
            (new ResourceList([$ingress]))
                ->setDetailActionsDisabled()
                ->setViewMode(ViewMode::Minimal)
        );

        $this->addControl(new QuickActions($ingress, $favorite));

        $this->addContent(new IngressDetail($ingress));
    }
}

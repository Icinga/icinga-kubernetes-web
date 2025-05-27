<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Controls\QuickActions;
use Icinga\Module\Kubernetes\Web\Detail\ServiceDetail;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ServiceController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_SERVICES);

        $this->addTitleTab($this->translate('Service'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $service = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_SERVICES, Service::on(Database::connection()))
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

        if ($service === null) {
            $this->httpNotFound($this->translate('Service not found'));
        }

        $this->addControl(
            (new ResourceList([$service]))
                ->setDetailActionsDisabled()
                ->setViewMode(ViewMode::Minimal)
        );

        $this->addControl(new QuickActions($service, $favorite));

        $this->addContent(new ServiceDetail($service));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Controller\EventsTab;
use Icinga\Module\Kubernetes\Web\Controls\QuickActions;
use Icinga\Module\Kubernetes\Web\Detail\PodDetail;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\Widget\Yaml;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class PodController extends Controller
{
    use EventsTab;

    protected Pod $pod;

    public function init(): void
    {
        $this->assertPermission(Auth::SHOW_PODS);

        $this->createResourceTabs($this->translate('Pod'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $pod = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_PODS, Pod::on(Database::connection()))
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

        if ($pod === null) {
            $this->httpNotFound($this->translate('Pod not found'));
        }

        $this->addControl(
            (new ResourceList([$pod]))
                ->setDetailActionsDisabled()
                ->setViewMode(ViewMode::Minimal)
        );

        $this->addControl(new QuickActions($pod, $favorite));

        $this->pod = $pod;
    }

    public function indexAction(): void
    {
        $this->addContent(new PodDetail($this->pod));
    }

    public function yamlAction(): void
    {
        $this->assertPermission(Auth::SHOW_YAML);

        $this->addContent(new Yaml($this->pod->yaml));
    }
}

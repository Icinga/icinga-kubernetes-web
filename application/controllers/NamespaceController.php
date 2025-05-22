<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\NamespaceDetail;
use Icinga\Module\Kubernetes\Web\QuickActions;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class NamespaceController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_NAMESPACES);

        $this->addTitleTab($this->translate('Namespace'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $namespace = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_NAMESPACES, NamespaceModel::on(Database::connection()))
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

        if ($namespace === null) {
            $this->httpNotFound($this->translate('Namespace not found'));
        }

        $this->addControl(
            (new ResourceList([$namespace]))
                ->setDetailActionsDisabled()
                ->setViewMode(ViewMode::Minimal)
        );

        $this->addControl(new QuickActions($namespace, $favorite));

        $this->addContent(new NamespaceDetail($namespace));
    }
}

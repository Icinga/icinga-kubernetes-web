<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\DaemonSetDetail;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\QuickActions;
use Icinga\Module\Kubernetes\Web\ViewModeSwitcher;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class DaemonsetController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_DAEMON_SETS);

        $this->addTitleTab($this->translate('Daemon Set'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $daemonSet = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_DAEMON_SETS, DaemonSet::on(Database::connection()))
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

        if ($daemonSet === null) {
            $this->httpNotFound($this->translate('Daemon Set not found'));
        }

        $this->addControl(
            (new ResourceList([$daemonSet]))
                ->setDetailActionsDisabled()
                ->setViewMode(ViewModeSwitcher::VIEW_MODE_MINIMAL)
        );

        $this->addControl(new QuickActions($daemonSet, $favorite));

        $this->addContent(new DaemonSetDetail($daemonSet));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\NodeDetail;
use Icinga\Module\Kubernetes\Web\NodeList;
use Icinga\Module\Kubernetes\Web\ViewModeSwitcher;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class NodeController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_NODES);

        $this->addTitleTab($this->translate('Node'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $node = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_NODES, Node::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($node === null) {
            $this->httpNotFound($this->translate('Node not found'));
        }

        $this->addControl(
            (new NodeList([$node]))
                ->setActionList(false)
                ->setViewMode(ViewModeSwitcher::VIEW_MODE_DETAILED)
        );

        $this->addContent(new NodeDetail($node));
    }
}

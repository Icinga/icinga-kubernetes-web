<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\NamespaceDetail;
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

        if ($namespace === null) {
            $this->httpNotFound($this->translate('Namespace not found'));
        }

        $this->addContent(new NamespaceDetail($namespace));
    }
}

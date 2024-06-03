<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Web\NamespaceDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
use Ramsey\Uuid\Uuid;

class NamespaceController extends CompatController
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Namespace'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var NamespaceModel $namespace */
        $namespace = NamespaceModel::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($namespace === null) {
            $this->httpNotFound($this->translate('Namespace not found'));
        }

        $this->addContent(new NamespaceDetail($namespace));
    }
}

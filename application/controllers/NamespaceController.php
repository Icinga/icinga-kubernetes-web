<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Web\NamespaceDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class NamespaceController extends CompatController
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Namespace'));

        /** @var NamespaceModel $namespace */
        $namespace = NamespaceModel::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($namespace === null) {
            $this->httpNotFound($this->translate('Namespace not found'));
        }

        $this->addContent(new NamespaceDetail($namespace));
    }
}

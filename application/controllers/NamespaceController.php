<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Web\NamespaceDetail;
use Icinga\Module\Kubernetes\Common\Database;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class NamespaceController extends CompatController
{
    public function indexAction(): void
    {
        $name = $this->params->get('name');
        $id = $this->params->getRequired('id');

        $this->addTitleTab("Namespace $name");

        $query = NamespaceModel::on(Database::connection())
            ->filter(Filter::all(
                Filter::equal('namespace.id', $id)
            ));

        /** @var NamespaceModel $namespace */
        $namespace = $query->first();
        if ($namespace === null) {
            $this->httpNotFound($this->translate('Namespace not found'));
        }

        $this->addContent(new NamespaceDetail($namespace));
    }
}

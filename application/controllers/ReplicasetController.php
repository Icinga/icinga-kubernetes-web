<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\ReplicaSetDetail;
use ipl\Stdlib\Filter;

class ReplicasetController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Replica Set'));

        /** @var ReplicaSet $replicaSet */
        $replicaSet = ReplicaSet::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($replicaSet === null) {
            $this->httpNotFound($this->translate('Replica Set not found'));
        }

        $this->addContent(new ReplicaSetDetail($replicaSet));
    }
}

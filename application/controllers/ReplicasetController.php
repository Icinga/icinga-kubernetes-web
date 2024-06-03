<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\ReplicaSetDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ReplicasetController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Replica Set'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var ReplicaSet $replicaSet */
        $replicaSet = ReplicaSet::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($replicaSet === null) {
            $this->httpNotFound($this->translate('Replica Set not found'));
        }

        $this->addContent(new ReplicaSetDetail($replicaSet));
    }
}

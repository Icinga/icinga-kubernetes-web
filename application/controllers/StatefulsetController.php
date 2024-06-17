<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\StatefulSetDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class StatefulsetController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Stateful Set'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var StatefulSet $statefulSet */
        $statefulSet = StatefulSet::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($statefulSet === null) {
            $this->httpNotFound($this->translate('Stateful Set not found'));
        }

        $this->addContent(new StatefulSetDetail($statefulSet));
    }
}

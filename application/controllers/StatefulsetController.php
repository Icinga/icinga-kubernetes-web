<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\StatefulSetDetail;
use Icinga\Module\Kubernetes\Web\StatefulSetList;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class StatefulsetController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_STATEFUL_SETS);

        $this->addTitleTab($this->translate('Stateful Set'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $statefulSet = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_STATEFUL_SETS, StatefulSet::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($statefulSet === null) {
            $this->httpNotFound($this->translate('Stateful Set not found'));
        }

        $this->addControl(new StatefulSetList([$statefulSet]));

        $this->addContent(new StatefulSetDetail($statefulSet));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\StatefulSetDetail;
use ipl\Stdlib\Filter;

class StatefulsetController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Stateful Set'));

        /** @var StatefulSet $statefulSet */
        $statefulSet = StatefulSet::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($statefulSet === null) {
            $this->httpNotFound($this->translate('Stateful Set not found'));
        }

        $this->addContent(new StatefulSetDetail($statefulSet));
    }
}

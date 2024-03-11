<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\DaemonSetDetail;
use ipl\Stdlib\Filter;

class DaemonsetController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Daemon Set'));

        /** @var DaemonSet $daemonSet */
        $daemonSet = DaemonSet::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($daemonSet === null) {
            $this->httpNotFound($this->translate('Daemon Set not found'));
        }

        $this->addContent(new DaemonSetDetail($daemonSet));
    }
}

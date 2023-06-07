<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\Dashboard;

class DashboardController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Dashboard'));

        $this->addContent(new Dashboard());
    }
}
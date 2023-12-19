<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Web\ConfigMapDetail;
use Icinga\Module\Kubernetes\Web\Controller;
use ipl\Stdlib\Filter;

class ConfigmapController extends Controller
{
    public function indexAction(): void
    {
        $id = $this->params->getRequired('id');

        $configMap = ConfigMap::on(Database::connection())
            ->filter(Filter::equal('id', $id))
            ->first();

        $this->addTitleTab("Config Map");

        $this->addContent(new ConfigMapDetail($configMap));
    }

    protected function getPageSize($default)
    {
        return parent::getPageSize($default ?? 50);
    }
}

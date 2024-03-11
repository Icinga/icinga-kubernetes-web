<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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
        $this->addTitleTab('Config Map');

        /** @var ConfigMap $configMap */
        $configMap = ConfigMap::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($configMap === null) {
            $this->httpNotFound($this->translate('Config Map not found'));
        }

        $this->addContent(new ConfigMapDetail($configMap));
    }
}

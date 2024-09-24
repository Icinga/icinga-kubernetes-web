<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Web\ConfigMapDetail;
use Icinga\Module\Kubernetes\Web\Controller;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ConfigmapController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_CONFIG_MAPS);

        $this->addTitleTab('Config Map');

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $configMap = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_CONFIG_MAPS, ConfigMap::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($configMap === null) {
            $this->httpNotFound($this->translate('Config Map not found'));
        }

        $this->addContent(new ConfigMapDetail($configMap));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Detail\ConfigMapDetail;
use Icinga\Module\Kubernetes\Web\Widget\Environment\NamespaceEnvironment;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ConfigmapController extends Controller
{
    protected ConfigMap $configMap;

    public function init(): void
    {
        $this->assertPermission(Auth::SHOW_CONFIG_MAPS);

        $this->createResourceTabs(
            $this->translate('Config Map'),
            ['environment' => $this->translate('Environment')],
            false
        );

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $configMap = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_CONFIG_MAPS, ConfigMap::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($configMap === null) {
            $this->httpNotFound($this->translate('Config Map not found'));
        }

        $this->configMap = $configMap;
    }

    public function indexAction(): void
    {
        $this->addContent(new ConfigMapDetail($this->configMap));
    }

    public function environmentAction(): void
    {
        $this->addContent(NamespaceEnvironment::forObject($this->configMap));
    }
}

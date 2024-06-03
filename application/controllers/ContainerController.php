<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Web\ContainerDetail;
use Icinga\Module\Kubernetes\Web\Controller;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ContainerController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Container'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var Container $container */
        $container = Container::on(Database::connection())
            ->with('log')
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($container === null) {
            $this->httpNotFound($this->translate('Container not found'));
        }

        $this->addContent(new ContainerDetail($container));
    }
}

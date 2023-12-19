<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Web\ContainerDetail;
use Icinga\Module\Kubernetes\Web\Controller;
use ipl\Stdlib\Filter;

class ContainerController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Container'));

        /** @var Container $container */
        $container = Container::on(Database::connection())
            ->with('log')
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($container === null) {
            $this->httpNotFound($this->translate('Container not found'));
        }

        $this->addContent(new ContainerDetail($container));
    }
}

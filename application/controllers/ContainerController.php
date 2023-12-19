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

        $id = $this->params->getRequired('id');

        $query = Container::on(Database::connection())
            ->with('log')
            ->filter(Filter::all(
                Filter::equal('container.id', $id)
            ));

        /** @var Container $container */
        $container = $query->first();
        if ($container === null) {
            $this->httpNotFound($this->translate('Container not found'));
        }

//        $this->addControl(
//            (new PodList($query))
//                ->setNoSubjectLink()
//        );
//        $this->controls->addAttributes(['class' => 'pod-detail']);

        $this->addContent(new ContainerDetail($container));
    }
}

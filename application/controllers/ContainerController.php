<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\PodDetail;
use ipl\Stdlib\Filter;

class ContainerController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Pod'));

        $namespace = $this->params->getRequired('namespace');
        $name = $this->params->getRequired('name');

        $query = Pod::on(Database::connection())
            ->filter(Filter::all(
                Filter::equal('pod.namespace', $namespace),
                Filter::equal('pod.name', $name)
            ));

        /** @var Pod $pod */
        $pod = $query->first();
        if ($pod === null) {
            $this->httpNotFound($this->translate('Pod not found'));
        }

//        $this->addControl(
//            (new PodList($query))
//                ->setNoSubjectLink()
//        );
//        $this->controls->addAttributes(['class' => 'pod-detail']);

        $this->addContent(new PodDetail($pod));
    }
}

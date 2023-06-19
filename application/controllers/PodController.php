<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Web\PodDetail;
use Icinga\Module\Kubernetes\Common\Database;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class PodController extends CompatController
{
    public function indexAction(): void
    {
        $namespace = $this->params->get('namespace');
        $name = $this->params->get('name');
        $id = $this->params->getRequired('id');

        $this->addTitleTab("Pod $namespace/$name");

        $query = Pod::on(Database::connection())
            ->filter(Filter::all(
                Filter::equal('pod.id', $id)
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

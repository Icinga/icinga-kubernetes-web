<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Web\PodDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class PodController extends CompatController
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Pod'));

        /** @var Pod $pod */
        $pod = Pod::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($pod === null) {
            $this->httpNotFound($this->translate('Pod not found'));
        }

        $this->addContent(new PodDetail($pod));
    }
}

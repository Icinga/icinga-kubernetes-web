<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Web\PodDetail;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
use Ramsey\Uuid\Uuid;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class PodController extends CompatController
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Pod'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var Pod $pod */
        $pod = Pod::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($pod === null) {
            $this->httpNotFound($this->translate('Pod not found'));
        }

        $this->addControl(
            new HtmlElement('div', new Attributes(['class' => 'detail-header']),
                new StateBall('critical'),
                new Text($pod->namespace),
                new Text(' / '),
                new Text($pod->name),
                new Text($pod->created->format('Y-m-d H:i:s')),
                new HtmlElement('br'),
                new Text($pod->uid),
                new Text(' / '),
                new Text($pod->resource_version),
                new HtmlElement('hr')
            )
        );

        $this->addContent(new PodDetail($pod));
    }
}

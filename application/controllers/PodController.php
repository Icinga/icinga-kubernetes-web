<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Icingadb\Model\Behavior\ActionAndNoteUrl;
use Icinga\Module\Icingadb\Model\State;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Web\EndpointTable;
use Icinga\Module\Kubernetes\Web\PodDetail;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
use Ramsey\Uuid\Uuid;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeSince;

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
            new HtmlElement('div', new Attributes(['class' => 'resource-list item-list']),
                new HtmlElement('div', new Attributes(['class' => 'list-item']),
                    new HtmlElement('div', new Attributes(['class' => 'visual']),
                        new StateBall($pod->icinga_state, StateBall::SIZE_MEDIUM_LARGE)),
                    new HtmlElement('div', new Attributes(['class' => 'main']),
                        new HtmlElement('header', null,
                            new HtmlElement('div', new Attributes(['class' => 'title']),
                                new HtmlElement('span', new Attributes(['class' => 'subject']),
                                    new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-pod'])),
                                    new Text($pod->name)
                                ),
                                new Text(' is '),
                                new HtmlElement('span', new Attributes(['class' => 'state-text']), new Text($pod->icinga_state))
                            ),
                            new TimeSince('1716284368')
                        ),
                        new HtmlElement('section', new Attributes(['class' => 'caption']),
                            new HorizontalKeyValue('created', new Text('2024-12-23 12:24'))
                        ),
                        /*
                        new HtmlElement('br'),
                        new Text($pod->uid),
                        new Text(' / '),
                        new Text($pod->resource_version),
                        */
                        new HtmlElement('footer', null,
                            new HtmlElement('span', new Attributes(['class' => 'badge-namespace']),
                                new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-ns'])),
                                new Text($pod->namespace)
                            )
                        )
                    )
                )
            )
        );

        $this->addContent(new PodDetail($pod));
    }
}

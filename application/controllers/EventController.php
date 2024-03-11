<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\EventDetail;
use ipl\Stdlib\Filter;

class EventController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Event'));

        /** @var Event $event */
        $event = Event::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($event === null) {
            $this->httpNotFound($this->translate('Event not found'));
        }

        $this->addContent(new EventDetail($event));
    }
}

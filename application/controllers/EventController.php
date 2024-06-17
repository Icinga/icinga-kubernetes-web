<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\EventDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class EventController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Event'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var Event $event */
        $event = Event::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($event === null) {
            $this->httpNotFound($this->translate('Event not found'));
        }

        $this->addContent(new EventDetail($event));
    }
}

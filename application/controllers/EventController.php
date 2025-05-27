<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Detail\EventDetail;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class EventController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_EVENTS);

        $this->addTitleTab($this->translate('Event'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $event = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_EVENTS, Event::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($event === null || ! Auth::getInstance()->canList($event->reference_kind)) {
            $this->httpNotFound($this->translate('Event not found'));
        }

        $this->addContent(new EventDetail($event));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Web\EventList;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;

class EventsController extends ListController
{
    protected function getContentClass(): string
    {
        return EventList::class;
    }

    protected function getQuery(): Query
    {
        $events = Event::on(Database::connection());

        Auth::getInstance()->applyRestrictions($events);

        return $events;
    }

    protected function getSortColumns(): array
    {
        return ['event.created desc' => $this->translate('Created')];
    }

    protected function getTitle(): string
    {
        return $this->translate('Events');
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Permissions;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Web\EventList;
use Icinga\Module\Kubernetes\Web\ListController;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;

class EventsController extends ListController
{
    protected function getContentClass(): string
    {
        return EventList::class;
    }

    protected function getQuery(): Query
    {
        $events = Event::on(Database::connection());

        foreach ($events as $event) {
            if (Permissions::getInstance()->canList($event->reference_kind) === false) {
                $events->filter(Filter::unequal('referent_uuid', $event->referent_uuid));
            }
        }

        Auth::getInstance()->applyRestrictions($events, $this->getPermission());

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

    protected function getPermission(): string
    {
        return Auth::SHOW_EVENTS;
    }
}

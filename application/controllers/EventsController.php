<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

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
        return Event::on(Database::connection());
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

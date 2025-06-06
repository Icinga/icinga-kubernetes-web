<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Web\Controller\ListController;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;

class EventsController extends ListController
{
    protected function getQuery(): Query
    {
        $events = Auth::getInstance()->withRestrictions(Auth::SHOW_EVENTS, Event::on(Database::connection()));

        $allowedKinds = [];
        foreach (Auth::PERMISSIONS as $kind => $permission) {
            if (Auth::getInstance()->canList($kind)) {
                $allowedKinds[] = $kind;
            }
        }

        if (! empty($allowedKinds)) {
            $events->filter(Filter::equal('reference_kind', $allowedKinds));
        }

        return $events;
    }

    protected function getSortColumns(): array
    {
        return [
            'event.last_seen desc' => $this->translate('Last Seen'),
            'event.created desc' => $this->translate('Created')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Events');
    }

    protected function getPermission(): string
    {
        return Auth::SHOW_EVENTS;
    }

    protected function getIgnoredViewModes(): array
    {
        return [ViewMode::Detailed];
    }

    protected function getFavorable(): false
    {
        return false;
    }
}

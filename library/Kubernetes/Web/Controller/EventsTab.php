<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Controller;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\TBD\ObjectSuggestions;
use Icinga\Module\Kubernetes\Web\Controls\ViewModeSwitcher;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\SearchControls;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;
use Ramsey\Uuid\Uuid;

/**
 * Events tab of a resource detail controller
 *
 * Lists the events that reference the resource given by the `id` parameter,
 * with the same controls as the events list.
 */
trait EventsTab
{
    use SearchControls;

    public function eventsAction(): void
    {
        $this->assertPermission(Auth::SHOW_EVENTS);

        // Shift the ID so that the search bar doesn't parse it as a filter.
        $uuid = Uuid::fromString($this->params->shiftRequired('id'))->getBytes();

        $events = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_EVENTS, Event::on(Database::connection()))
            ->filter(Filter::equal('reference_uuid', $uuid));

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl($events, [
            'event.last_seen desc' => $this->translate('Last Seen'),
            'event.created desc'   => $this->translate('Created')
        ]);
        $paginationControl = $this->createPaginationControl($events);
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $preserveParams = [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            'id'
        ];
        $requestParams = Url::fromRequest()->onlyWith($preserveParams)->getParams();
        $path = $this->getEventsPath();

        $searchBar = $this->createSearchBar($events, $preserveParams)
            ->setEditorUrl(Url::fromPath("$path-search-editor")->setParams($requestParams))
            ->setSuggestionUrl(Url::fromPath("$path-complete")->setParams(clone $requestParams));

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();

                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $events->filter($filter);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $this->addContent((new ResourceList($events))->setViewMode($viewModeSwitcher->getViewMode()));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function eventsCompleteAction(): void
    {
        $this->assertPermission(Auth::SHOW_EVENTS);

        $this->getDocument()->addHtml(
            (new ObjectSuggestions())
                ->setModel(Event::class)
                ->forRequest($this->getServerRequest())
        );
    }

    public function eventsSearchEditorAction(): void
    {
        $this->assertPermission(Auth::SHOW_EVENTS);

        $preserveParams = [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
            'id'
        ];
        $path = $this->getEventsPath();

        $editor = $this->createSearchEditor(
            Event::on(Database::connection()),
            Url::fromPath($path),
            $preserveParams
        );
        $editor->setSuggestionUrl(
            Url::fromPath("$path-complete")->setParams(Url::fromRequest()->onlyWith($preserveParams)->getParams())
        );

        $this->getDocument()->addHtml($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }

    protected function getIgnoredViewModes(): array
    {
        return [ViewMode::Detailed];
    }

    /**
     * Get the path of the events tab of the current controller
     *
     * @return string
     */
    protected function getEventsPath(): string
    {
        return 'kubernetes/' . $this->getRequest()->getControllerName() . '/events';
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\TBD\ObjectSuggestions;
use ipl\Orm\Query;
use ipl\Web\Compat\SearchControls;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

abstract class ListController extends Controller
{
    use SearchControls;

    abstract protected function getContentClass(): string;

    abstract protected function getQuery(): Query;

    abstract protected function getSortColumns(): array;

    abstract protected function getTitle(): string;

    public function indexAction(): void
    {
        $this->addTitleTab($this->getTitle());

        $q = $this->getQuery();

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl($q, $this->getSortColumns());
        $paginationControl = $this->createPaginationControl($q);
        $searchBar = $this->createSearchBar($q, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
        ]);

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

        $q->filter($filter);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $this->addContent(new ($this->getContentClass())($q));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function completeAction(): void
    {
        $this->getDocument()->addHtml(
            (new ObjectSuggestions())
                ->setModel($this->getModelClass())
                ->forRequest($this->getServerRequest())
        );
    }

    public function searchEditorAction(): void
    {
        $this->setTitle(t('Adjust Filter'));

        $this->getDocument()->addHtml($this->createSearchEditor($this->getQuery(), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
        ]));
    }

    protected function getModelClass(): string
    {
        return get_class($this->getQuery()->getModel());
    }
}

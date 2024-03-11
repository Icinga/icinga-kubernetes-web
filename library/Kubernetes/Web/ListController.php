<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\TBD\ObjectSuggestions;
use ipl\Orm\Query;
use ipl\Web\Compat\SearchControls;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

abstract class ListController extends Controller
{
    use SearchControls;

    public function completeAction(): void
    {
        $this->getDocument()->addHtml(
            (new ObjectSuggestions())
                ->setModel($this->getModelClass())
                ->forRequest($this->getServerRequest())
        );
    }

    protected function getModelClass(): string
    {
        return get_class($this->getQuery()->getModel());
    }

    abstract protected function getQuery(): Query;

    public function indexAction(): void
    {
        $this->addTitleTab($this->getTitle());

        $q = $this->getQuery();

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl($q, $this->getSortColumns());
        $paginationControl = $this->createPaginationControl($q);
        $searchBar = $this->createSearchBar($q, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam()
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

        $contentClass = $this->getContentClass();
        $this->addContent(new $contentClass($q));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    abstract protected function getTitle(): string;

    abstract protected function getSortColumns(): array;

    abstract protected function getContentClass(): string;

    public function searchEditorAction(): void
    {
        $this->setTitle($this->translate('Adjust Filter'));

        $this->getDocument()->addHtml($this->createSearchEditor($this->getQuery(), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM
        ]));
    }
}

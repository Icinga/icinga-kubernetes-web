<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Controller;

use Icinga\Exception\Http\HttpMethodNotAllowedException;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\TBD\ObjectSuggestions;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\Widget\FavoriteToggle;
use Icinga\Module\Kubernetes\Web\Widget\MoveFavoriteForm;
use Icinga\Web\Session;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\SearchControls;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;
use Ramsey\Uuid\Uuid;

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
        $this->assertPermission($this->getPermission());

        $clusterUuid = Session::getSession()
            ->getNamespace('kubernetes')
            ->get('cluster_uuid');

        $title = $this->getTitle();
        if ($clusterUuid !== null) {
            $clusterName = Cluster::on(Database::connection())
                ->columns('name')
                ->filter(Filter::equal('uuid', Uuid::fromString($clusterUuid)->getBytes()))
                ->first()
                ->name ?? $clusterUuid;

            $title .= " ($clusterName)";
        } else {
            $title .= " ({$this->translate('All clusters')})";
        }
        $this->addTitleTab($title);

        $q = Auth::getInstance()->withRestrictions($this->getPermission(), $this->getQuery());

        if ($clusterUuid !== null) {
            $q->filter(Filter::equal('cluster_uuid', Uuid::fromString($clusterUuid)->getBytes()));
        }

        $favoriteToggleActive = false;
        if ($this->getFavorable()) {
            $favoriteToggle = $this->createFavoriteToggle($q);
            $favoriteToggleActive = $favoriteToggle->getValue($favoriteToggle->getFavoriteParam()) === 'y';
        }

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl(
            $q,
            $this->getSortColumns()
            + ($favoriteToggleActive ? ['favorite.priority desc' => $this->translate('Custom Order')] : [])
        );
        $paginationControl = $this->createPaginationControl($q);
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $searchBar = $this->createSearchBar($q, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            (isset($favoriteToggle) ? $favoriteToggle->getFavoriteParam() : ''),
            'columns',
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

        if ($sortControl->getPopulatedValue($sortControl->getSortParam()) === 'favorite.priority desc') {
            $this->content->addAttributes(['class' => 'custom-sortable']);
        }

        if ($favoriteToggleActive) {
            $paginationControl->setDefaultPageSize(1000);
        }

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        if (! $favoriteToggleActive) {
            $this->addControl($limitControl);
        }
        $this->addControl($viewModeSwitcher);
        if ($this->getFavorable()) {
            $this->addControl($favoriteToggle);
        }
        $this->addControl($searchBar);

        $this->addContent((new ResourceList($q))->setViewMode($viewModeSwitcher->getViewMode()));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    /**
     * Handle the reordering via drag & drop.
     *
     * @return void
     *
     * @throws HttpMethodNotAllowedException
     */
    public function moveFavoriteAction(): void
    {
        $this->assertHttpMethod('POST');

        (new MoveFavoriteForm(Database::connection()))
            ->on(MoveFavoriteForm::ON_SUCCESS, function () {
                // Suppress handling XHR response and disable view rendering,
                // so we can use the form in the list without the page reloading.
                $this->getResponse()->setHeader('X-Icinga-Container', 'ignore', true);
                $this->_helper->viewRenderer->setNoRender();
            })->handleRequest($this->getServerRequest());
    }

    abstract protected function getTitle(): string;

    abstract protected function getSortColumns(): array;

    abstract protected function getPermission(): string;

    abstract protected function getFavorable(): bool;

    public function searchEditorAction(): void
    {
        $this->setTitle($this->translate('Adjust Filter'));

        $this->getDocument()->addHtml($this->createSearchEditor($this->getQuery(), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM
        ]));
    }

    /**
     * Create and return the FavoriteToggle
     *
     * This automatically shifts the favorite URL parameter from {@link $params}.
     *
     * @param Query $query
     *
     * @return FavoriteToggle
     */
    public function createFavoriteToggle(
        Query $query
    ): FavoriteToggle {
        $favoriteToggle = new FavoriteToggle();
        $defaultFavoriteParam = $favoriteToggle->getFavoriteParam();
        $favoriteParam = $this->params->shift($defaultFavoriteParam);
        $favoriteToggle->populate([
            $defaultFavoriteParam => $favoriteParam
        ]);

        $favoriteToggle->on(FavoriteToggle::ON_SUCCESS, function (FavoriteToggle $favoriteToggle) use (
            $query,
            $defaultFavoriteParam
        ) {
            $favoriteParam = $favoriteToggle->getValue($defaultFavoriteParam);

            $requestUrl = Url::fromRequest();

            // Redirect if favorite param has changed to update the URL
            if (isset($favoriteParam) && $requestUrl->getParam($defaultFavoriteParam) !== $favoriteParam) {
                $requestUrl->setParam($defaultFavoriteParam, $favoriteParam);
                if (
                    $favoriteParam === 'n'
                    && $requestUrl->getParam(SortControl::DEFAULT_SORT_PARAM) === 'favorite.priority desc'
                ) {
                    $requestUrl->remove(SortControl::DEFAULT_SORT_PARAM);
                }

                $this->redirectNow($requestUrl);
            }
        })->handleRequest($this->getServerRequest());

        if ($favoriteToggle->getValue($defaultFavoriteParam) === 'y') {
            $query->with('favorite')
                ->filter(Filter::equal('favorite.username', Auth::getInstance()->getUser()->getUsername()));
        }

        return $favoriteToggle;
    }
}

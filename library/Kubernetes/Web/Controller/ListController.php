<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Controller;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Exception\Http\HttpMethodNotAllowedException;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\TBD\ObjectSuggestions;
use Icinga\Module\Kubernetes\Web\Controls\ViewModeSwitcher;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\Widget\FavoriteToggle;
use Icinga\Module\Kubernetes\Web\Widget\MoveFavoriteForm;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\Json;
use Icinga\Web\Session;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\SearchControls;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\PaginationControl;
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

    protected function getIgnoredViewModes(): array
    {
        return [];
    }

    public function searchEditorAction(): void
    {
        $this->setTitle($this->translate('Adjust Filter'));

        $this->getDocument()->addHtml($this->createSearchEditor($this->getQuery(), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM
        ]));
    }

    /**
     * Create and return the ViewModeSwitcher
     *
     * This automatically shifts the view mode URL parameter from {@link $params}.
     *
     * @param PaginationControl $paginationControl
     * @param LimitControl      $limitControl
     * @param bool              $verticalPagination
     *
     * @return ViewModeSwitcher
     */
    public function createViewModeSwitcher(
        PaginationControl $paginationControl,
        LimitControl $limitControl,
        bool $verticalPagination = false
    ): ViewModeSwitcher {
        $viewModeSwitcher = new ViewModeSwitcher();
        $viewModeSwitcher->setIdProtector([$this->getRequest(), 'protectId']);

        $ignoredViewModes = $this->getIgnoredViewModes();

        // Check if only one or no view mode should be selectable. If so don't show view mode switcher in the Web UI.
        // This is the case if all view modes are ignored or only one view mode is not ignored.
        if (count($ignoredViewModes) >= count(ViewModeSwitcher::$viewModes) - 1) {
            $viewModeSwitcher->addIgnoredViewModes(...array_map(
                fn ($value) => ViewMode::from($value),
                array_keys(ViewModeSwitcher::$viewModes)
            ));
        } else {
            $viewModeSwitcher->addIgnoredViewModes(...$ignoredViewModes);
        }

        $user = $this->Auth()->getUser();
        if (($preferredModes = $user->getAdditional('kubernetes.view_modes')) === null) {
            try {
                $preferredModes = Json::decode(
                    $user->getPreferences()->getValue('kubernetes', 'view_modes', '[]'),
                    true
                );
            } catch (JsonDecodeException $e) {
                Logger::error('Failed to load preferred view modes for user "%s": %s', $user->getUsername(), $e);
                $preferredModes = [];
            }

            $user->setAdditional('kubernetes.view_modes', $preferredModes);
        }

        $requestRoute = $this->getRequest()->getUrl()->getPath();
        if (isset($preferredModes[$requestRoute])) {
            $viewModeSwitcher->setDefaultViewMode(ViewMode::from($preferredModes[$requestRoute]));
        } else {
            if (! in_array(ViewMode::Detailed, $ignoredViewModes)) {
                $viewModeSwitcher->setDefaultViewMode(ViewMode::Detailed);
            } elseif (! in_array(ViewMode::Common, $ignoredViewModes)) {
                $viewModeSwitcher->setDefaultViewMode(ViewMode::Common);
            } else {
                $viewModeSwitcher->setDefaultViewMode(ViewMode::Minimal);
            }
        }

        $viewModeSwitcher->populate([
            $viewModeSwitcher->getViewModeParam() => $this->params->shift($viewModeSwitcher->getViewModeParam())
        ]);

        $session = $this->Window()->getSessionNamespace('kubernetes-viewmode-' . $this->Window()->getContainerId());

        $viewModeSwitcher->on(
            ViewModeSwitcher::ON_SUCCESS,
            function (ViewModeSwitcher $viewModeSwitcher) use (
                $user,
                $preferredModes,
                $paginationControl,
                $verticalPagination,
                &$session
            ) {
                $viewMode = $viewModeSwitcher->getValue($viewModeSwitcher->getViewModeParam());
                $requestUrl = Url::fromRequest();

                $preferredModes[$requestUrl->getPath()] = $viewMode;
                $user->setAdditional('kubernetes.view_modes', $preferredModes);

                try {
                    $preferencesStore = PreferencesStore::create(new ConfigObject([
                        //TODO: Don't set store key as it will no longer be needed once we drop support for
                        // lower version of icingaweb2 then v2.11.
                        //https://github.com/Icinga/icingaweb2/pull/4765
                        'store'    => Config::app()->get('global', 'config_backend', 'db'),
                        'resource' => Config::app()->get('global', 'config_resource')
                    ]), $user);
                    $preferencesStore->load();
                    $preferencesStore->save(
                        new Preferences(['kubernetes' => ['view_modes' => Json::encode($preferredModes)]])
                    );
                } catch (Exception $e) {
                    Logger::error('Failed to save preferred view mode for user "%s": %s', $user->getUsername(), $e);
                }

                $pageParam = $paginationControl->getPageParam();
                $limitParam = LimitControl::DEFAULT_LIMIT_PARAM;
                $currentPage = $paginationControl->getCurrentPageNumber();

                $requestUrl->setParam($viewModeSwitcher->getViewModeParam(), $viewMode);
                if (! $requestUrl->hasParam($limitParam)) {
                    if ($viewMode === ViewMode::Minimal) {
                        $session->set('previous_page', $currentPage);
                        $session->set('request_path', $requestUrl->getPath());

                        $limit = $paginationControl->getLimit();
                        if (! $verticalPagination) {
                            // We are computing it based on the first element being rendered on this current page
                            $currentPage = (int) (floor((($currentPage * $limit) - $limit) / ($limit * 2)) + 1);
                        } else {
                            $currentPage = (int) (round($currentPage * $limit / ($limit * 2)));
                        }

                        $session->set('current_page', $currentPage);
                    } elseif (
                        $viewModeSwitcher->getDefaultViewMode() === ViewMode::Minimal
                    ) {
                        $limit = $paginationControl->getLimit();
                        if ($currentPage === $session->get('current_page')) {
                            // No other page numbers have been selected, i.e the user only
                            // switches back and forth without changing the page numbers
                            $currentPage = $session->get('previous_page');
                        } elseif (! $verticalPagination) {
                            $currentPage = (int) (floor((($currentPage * $limit) - $limit) / ($limit / 2)) + 1);
                        } else {
                            $currentPage = (int) (floor($currentPage * $limit / ($limit / 2)));
                        }

                        $session->clear();
                    }

                    if (($requestUrl->hasParam($pageParam) && $currentPage > 1) || $currentPage > 1) {
                        $requestUrl->setParam($pageParam, $currentPage);
                    } else {
                        $requestUrl->remove($pageParam);
                    }
                }

                $this->redirectNow($requestUrl);
            }
        )->handleRequest(ServerRequest::fromGlobals());

        $viewMode = $viewModeSwitcher->getViewMode();
        if ($viewMode === ViewMode::Minimal || $viewMode === ViewMode::Common) {
            $hasLimitParam = Url::fromRequest()->hasParam($limitControl->getLimitParam());

            if ($paginationControl->getDefaultPageSize() <= LimitControl::DEFAULT_LIMIT && ! $hasLimitParam) {
                $paginationControl->setDefaultPageSize($paginationControl->getDefaultPageSize() * 2);
                $limitControl->setDefaultLimit($limitControl->getDefaultLimit() * 2);

                $paginationControl->apply();
            }
        }

        $requestPath = $session->get('request_path');
        if ($requestPath && $requestPath !== $requestRoute) {
            $session->clear();
        }

        return $viewModeSwitcher;
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

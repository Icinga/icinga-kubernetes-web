<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\TBD\ObjectSuggestions;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\Json;
use Icinga\Web\Session;
use ipl\Html\Html;
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

        $this->addTitleTab($this->getTitle());

        $q = Auth::getInstance()->withRestrictions($this->getPermission(), $this->getQuery());

        $clusterUuid = Session::getSession()
            ->getNamespace('kubernetes')
            ->get('cluster_uuid');
        if ($clusterUuid !== null) {
            $q->filter(Filter::equal('cluster_uuid', Uuid::fromString($clusterUuid)->getBytes()));
        }

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl($q, $this->getSortColumns());
        $paginationControl = $this->createPaginationControl($q);

        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $searchBar = $this->createSearchBar($q, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            'columns'
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
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $favorites = Favorite::on(Database::connection())
            ->filter(Filter::equal('username', Auth::getInstance()->getUser()->getUsername()));

        $favoriteFilter = [];

        foreach ($favorites as $favorite) {
            $favoriteFilter[] = Filter::equal('uuid', $favorite->resource_uuid);
        }

        $modelClass = $this->getModelClass();
        $contentClass = $this->getContentClass();

        if (! empty($favoriteFilter)) {
            $favoriteResources = $modelClass::on(Database::connection())
                ->filter(
                    Filter::all(
                        $filter,
                        Filter::any(...$favoriteFilter)
                    )
                );

            $this->addContent(
                (new $contentClass($favoriteResources, ['data-list-group' => 'fav', 'favorite-list' => '']))
                    ->addAttributes(['class' => 'collapsible'])
                    ->setViewMode($viewModeSwitcher->getViewMode())
            );
            $this->addContent(Html::hr());
        }

        $this->addContent(
            (new $contentClass($q, ['data-list-group' => 'fav']))
                ->setViewMode($viewModeSwitcher->getViewMode())
        );

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    abstract protected function getTitle(): string;

    abstract protected function getSortColumns(): array;

    abstract protected function getContentClass(): string;

    abstract protected function getPermission(): string;

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
            $viewModeSwitcher->addIgnoredViewModes(...array_keys(ViewModeSwitcher::$viewModes));
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
            $viewModeSwitcher->setDefaultViewMode($preferredModes[$requestRoute]);
        } else {
            if (! in_array('detailed', $ignoredViewModes)) {
                $viewModeSwitcher->setDefaultViewMode('detailed');
            } elseif (! in_array('common', $ignoredViewModes)) {
                $viewModeSwitcher->setDefaultViewMode('common');
            } else {
                $viewModeSwitcher->setDefaultViewMode('minimal');
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
                    if ($viewMode === 'minimal') {
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
                        $viewModeSwitcher->getDefaultViewMode() === 'minimal'
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
        if ($viewMode === 'minimal' || $viewMode === 'common') {
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
}

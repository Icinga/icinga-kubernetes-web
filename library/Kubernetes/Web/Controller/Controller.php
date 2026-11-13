<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Controller;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Web\Controls\ViewModeSwitcher;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\Json;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\PaginationControl;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

abstract class Controller extends CompatController
{
    protected ?Filter\Rule $filter = null;

    /**
     * Default auto refresh interval in seconds
     *
     * Automatically set if {@see $disableDefaultAutoRefresh} is `false` and
     * the auto-refresh interval has not been directly set via {@see autorefreshInterval} or
     * {@see setAutorefreshInterval()}.
     *
     * @var int
     */
    public const DEFAULT_AUTO_REFRESH_INTERVAL = 10;

    /**
     * Whether to disable default auto refresh
     *
     * If the auto-refresh interval is set directly via {@see autorefreshInterval} or {@see setAutorefreshInterval()},
     * this setting has no effect and does not need to be explicitly set to `true`.
     *
     * @var bool
     */
    protected bool $disableDefaultAutoRefresh = false;

    public function preDispatch(): void
    {
        if ($this->autorefreshInterval === null && ! $this->disableDefaultAutoRefresh) {
            $this->setAutorefreshInterval(static::DEFAULT_AUTO_REFRESH_INTERVAL);
        }

        parent::preDispatch();
    }

    /**
     * Create the detail and, if permitted, YAML tabs of the current resource and activate the current action's tab
     *
     * @param string $title
     *
     * @return void
     */
    protected function createResourceTabs(string $title): void
    {
        $this->setTitle($title);

        $path = 'kubernetes/' . $this->getRequest()->getControllerName();
        $params = ['id' => $this->params->getRequired('id')];

        $tabs = $this->getTabs()->add('index', [
            'label' => $title,
            'url'   => Url::fromPath($path, $params)
        ]);

        if (Auth::getInstance()->canShowYaml()) {
            $tabs->add('yaml', [
                'label' => $this->translate('YAML'),
                'url'   => Url::fromPath("$path/yaml", $params)
            ]);
        }

        $tabs->activate($this->getRequest()->getActionName());
    }

    protected function getIgnoredViewModes(): array
    {
        return [];
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
     * Get the filter created from query string parameters
     *
     * @return Filter\Rule
     */
    protected function getFilter(): Filter\Rule
    {
        if ($this->filter === null) {
            $this->filter = QueryString::parse((string) $this->params);
        }

        return $this->filter;
    }
}

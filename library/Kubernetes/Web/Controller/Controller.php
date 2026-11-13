<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Controller;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Module\Kubernetes\Web\Controls\ClusterSwitcher;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
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

    /**
     * Create the cluster switcher, populated from the URL parameter or the user's preferred cluster
     *
     * Only submitting the switcher saves the selected cluster as the user's preferred cluster
     * and redirects to the current URL with the URL parameter set. Requests that merely carry the
     * URL parameter, such as auto-refreshes or bookmarks, don't change the preference.
     *
     * @return ClusterSwitcher
     */
    protected function createClusterSwitcher(): ClusterSwitcher
    {
        $preferredCluster = $this->Auth()->getUser()->getPreferences()->getValue('kubernetes', 'cluster');

        return (new ClusterSwitcher())
            ->populate([
                ClusterSwitcher::DEFAULT_CLUSTER_PARAM => $this->params->shift(ClusterSwitcher::DEFAULT_CLUSTER_PARAM)
                    ?? $preferredCluster,
            ])
            ->on(ClusterSwitcher::ON_SUCCESS, function (ClusterSwitcher $clusterSwitcher) {
                $clusterUuid = $clusterSwitcher->getClusterUuid();
                // null (all clusters) removes the preference
                $this->saveKubernetesPreferences(['cluster' => $clusterUuid]);

                $this->redirectNow(Url::fromRequest()->setParam(
                    ClusterSwitcher::DEFAULT_CLUSTER_PARAM,
                    $clusterUuid ?? ClusterSwitcher::ALL_CLUSTERS,
                ));
            })
            ->handleRequest($this->getServerRequest());
    }

    /**
     * Append the selected cluster's name to the given title
     *
     * "All clusters" is appended if no cluster is selected, unless there is only one cluster.
     *
     * @param string $title
     * @param ClusterSwitcher $clusterSwitcher
     *
     * @return string
     */
    protected function getClusterTitle(string $title, ClusterSwitcher $clusterSwitcher): string
    {
        $clusterUuid = $clusterSwitcher->getClusterUuid();
        if ($clusterUuid !== null) {
            $clusterName = $clusterSwitcher->getClusters()[$clusterUuid] ?? $clusterUuid;

            return "$title ($clusterName)";
        }

        if ($clusterSwitcher->hasMultipleClusters()) {
            return "$title ({$this->translate('All clusters')})";
        }

        return $title;
    }

    /**
     * Merge the given values into the user's "kubernetes" preferences and persist them
     *
     * {@see PreferencesStore::save()} deletes every key of a section that isn't passed,
     * so the values are merged into the user's current preferences first. Null values remove the key.
     *
     * @param array<string, ?string> $values
     *
     * @return void
     */
    protected function saveKubernetesPreferences(array $values): void
    {
        $user = $this->Auth()->getUser();

        try {
            $preferencesStore = PreferencesStore::create(new ConfigObject([
                'resource' => Config::app()->get('global', 'config_resource'),
            ]), $user);
            $preferences = $user->getPreferences()->toArray();
            $preferences['kubernetes'] = array_filter(
                array_merge($preferences['kubernetes'] ?? [], $values),
                fn ($value) => $value !== null,
            );
            // save() diffs against the loaded state, so this is required to avoid duplicate inserts
            $preferencesStore->load();
            $preferencesStore->save(new Preferences(['kubernetes' => $preferences['kubernetes']]));

            // Preferences are cached in the session user, keep them in sync
            $user->setPreferences(new Preferences($preferences));
        } catch (Exception $e) {
            Logger::error('Failed to save preferences for user "%s": %s', $user->getUsername(), $e);
        }
    }
}

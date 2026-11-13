<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Controller;

use Icinga\Module\Kubernetes\Common\Auth;
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

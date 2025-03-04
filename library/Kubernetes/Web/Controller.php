<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
use ipl\Web\Filter\QueryString;

abstract class Controller extends CompatController
{
    protected Filter\Rule $filter;

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

    public function postDispatchXhr(): void
    {
        if ($this->autorefreshInterval === null && ! $this->disableDefaultAutoRefresh) {
            $this->setAutorefreshInterval(static::DEFAULT_AUTO_REFRESH_INTERVAL);
        }

        parent::postDispatchXhr();
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
     * Add the full-width class to the content element of BaseItemList instances.
     *
     * @param ValidHtml $content
     *
     * @return Controller
     */
    protected function addContent(ValidHtml $content)
    {
        if ($content instanceof BaseItemList) {
            $this->content->addAttributes(['class' => 'full-width']);
        }

        return parent::addContent($content);
    }
}

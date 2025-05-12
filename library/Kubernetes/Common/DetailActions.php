<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

trait DetailActions
{
    /** @var bool */
    protected $detailActionsDisabled = false;

    /**
     * Set whether this list should be an action-list
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setDetailActionsDisabled(bool $state = true): self
    {
        $this->detailActionsDisabled = $state;

        return $this;
    }

    /**
     * Get whether this list should be an action-list
     *
     * @return bool
     */
    public function getDetailActionsDisabled(): bool
    {
        return $this->detailActionsDisabled;
    }

    /**
     * Prepare this list as action-list
     *
     * @return $this
     */
    public function initializeDetailActions(): self
    {
        $this->getAttributes()
            ->registerAttributeCallback('class', function () {
                return $this->getDetailActionsDisabled() ? null : 'action-list';
            });

        return $this;
    }

    /**
     * Set the url to use for a single selected list item
     *
     * @param Url $url
     *
     * @return $this
     */
    protected function setDetailUrl(Url $url): self
    {
        $this->getAttributes()
            ->registerAttributeCallback('data-icinga-detail-url', function () use ($url) {
                return $this->getDetailActionsDisabled() ? null : (string) $url;
            });

        return $this;
    }

    /**
     * Associate the given element with the given single-selection filter
     *
     * @param BaseHtmlElement $element
     * @param Filter\Rule     $filter
     *
     * @return $this
     */
    public function addDetailFilterAttribute(BaseHtmlElement $element, Filter\Rule $filter): self
    {
        $element->getAttributes()
            ->registerAttributeCallback('data-action-item', function () {
                return ! $this->getDetailActionsDisabled();
            })
            ->registerAttributeCallback('data-icinga-detail-filter', function () use ($filter) {
                return $this->getDetailActionsDisabled() ? null : QueryString::render($filter);
            });

        return $this;
    }
}

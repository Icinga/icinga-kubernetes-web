<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

trait ViewMode
{
    /** @var string */
    protected $viewMode;

    /**
     * Get the view mode
     *
     * @return ?string
     */
    public function getViewMode()
    {
        return $this->viewMode;
    }

    /**
     * Set the view mode
     *
     * @param string $viewMode
     *
     * @return $this
     */
    public function setViewMode(string $viewMode): self
    {
        $this->viewMode = $viewMode;

        return $this;
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

/**
 * Trait to perform actions before assembling {@see HtmlDocument}s.
 */
trait BeforeAssemble
{
    private bool $beforeAssemble = false;

    public function ensureAssembled(): static
    {
        if ($this->hasBeenAssembled === false && ! $this->beforeAssemble) {
            $this->beforeAssemble = true;
            $this->beforeAssemble();
        }

        return parent::ensureAssembled();
    }

    /**
     * Hook method to perform actions before assembling the object.
     */
    protected function beforeAssemble(): void
    {
    }
}

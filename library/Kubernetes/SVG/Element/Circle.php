<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\SVGElement;

class Circle extends SVGElement
{
    private $cx;

    private $cy;

    private $r;

    public function setCx($cx)
    {
        $this->cx = $cx;

        return $this;
    }

    public function getCx()
    {
        return $this->cx;
    }

    public function setCy($cy)
    {
        $this->cy = $cy;

        return $this;
    }

    public function getCy()
    {
        return $this->cy;
    }

    public function setRadius($r)
    {
        $this->r = $r;

        return $this;
    }

    public function getRadius()
    {
        return $this->r;
    }

    public function render(): string
    {
        $this->setAttribute('cx', $this->getCx());
        $this->setAttribute('cy', $this->getCy());
        $this->setAttribute('r', $this->getRadius());

        return '<circle ' . $this->getAttributes() . '/>';
    }
}

<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\SVGElement;

class Polyline extends SVGElement
{
    private $points;

    private $color;

    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function __construct($points) {
        $this->points = $points;
    }

    public function render(): string
    {
        $this->setAttribute('points', $this->points);

        return '<polyline ' . $this->getAttributes() . '/>';
    }
}

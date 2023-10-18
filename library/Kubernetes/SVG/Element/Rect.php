<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\SVGElement;

class Rect extends SVGElement
{
    private $x;

    private $y;

    private $width;

    private $height;

    public function setX($x)
    {
        $this->x = $x;

        return $this;
    }

    public function getX()
    {
        return $this->x;
    }

    public function setY($y)
    {
        $this->y = $y;

        return $this;
    }

    public function getY()
    {
        return $this->y;
    }

    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function render(): string
    {
        $this->setAttribute('x', $this->x);
        $this->setAttribute('y', $this->y);
        $this->setAttribute('width', $this->getWidth());
        $this->setAttribute('height', $this->getHeight());

        return '<rect ' . $this->getAttributes() . '/>';
    }
}

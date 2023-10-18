<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\SVGElement;

class Text extends SVGElement
{
    private $x;

    private $y;

    private $text;

    private $fontsize;

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

    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setFontsize($fontsize)
    {
        $this->fontsize = $fontsize;

        return $this;
    }

    public function getFontsize()
    {
        return $this->fontsize;
    }

    public function render()
    {
        $this->setAttribute('x', $this->getX());
        $this->setAttribute('y', $this->getY());
        $this->setAttribute('fontsize', $this->getFontsize());

        return '<text ' . $this->getAttributes() . '>' . $this->getText() . '</text>';
    }
}

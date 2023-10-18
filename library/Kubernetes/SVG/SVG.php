<?php

namespace Icinga\Module\Kubernetes\SVG;

class SVG extends SVGElement
{
    private $contents = [];

    private $viewBox;

    private $width = 100;

    private $height = 100;

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

        return $height;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function addElement(SVGElement $element)
    {
        $this->contents[] = $element;
    }

    public function setViewBox($x, $y, $width, $height)
    {
        $this->viewBox = "$x, $y, $width, $height";
    }

    public function render(): string
    {
        $svg = "<svg ";
        $svg .= $this->viewBox ? "viewBox=\"$this->viewBox\" " : '';
        $svg .= $this->getAttributes() . ">\n";

        foreach ($this->contents as $element) {
            $svg .= $element->render() . "\n";
        }
        $svg .= "</svg>";

        return $svg;
    }
}

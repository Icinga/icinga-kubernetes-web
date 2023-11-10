<?php

namespace Icinga\Module\Kubernetes\SVG\Graph;

use Icinga\Module\Kubernetes\SVG\Element\Circle;
use Icinga\Module\Kubernetes\SVG\Element\Polyline;
use Icinga\Module\Kubernetes\SVG\Element\Rect;
use Icinga\Module\Kubernetes\SVG\SVG;

class LineGraph
{
    private $svg;

    private $values;

    private $rectColor = 'white';

    private $circleColor;

    private $lineColor;

    public function setSvg($svg)
    {
        $this->svg = $svg;

        return $this;
    }

    public function getSvg()
    {
        return $this->svg;
    }

    public function setRectColor($rectColor)
    {
        $this->rectColor = $rectColor;

        return $this;
    }

    public function getRectColor()
    {
        return $this->rectColor;
    }

    public function setCircleColor($circleColor)
    {
        $this->circleColor = $circleColor;

        return $this;
    }

    public function getCircleColor()
    {
        return $this->circleColor;
    }

    public function setLineColor($lineColor)
    {
        $this->lineColor = $lineColor;

        return $this;
    }

    public function getLineColor()
    {
        return $this->lineColor;
    }

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function assemble(): string
    {
        if ($this->getSvg() === null) {
            $this->svg = new SVG();
            $this->svg->setWidth(count($this->values) * 10);
            $this->svg->setHeight(max($this->values) + 10);
            $this->svg->setViewBox(0, 0, count($this->values) * 10, max($this->values) + 10);
        }

        $rect = new Rect();
        $rect
            ->setX(0)
            ->setY(0)
            ->setWidth($this->svg->getWidth())
            ->setHeight($this->svg->getHeight())
            ->setAttribute('fill', $this->getRectColor());

        $this->svg->addElement($rect);
        $xAxisLabel = 0;
        $xAxisGap = $rect->getWidth() / count($this->values);
        $yAxisLabel = $rect->getHeight();
        $points = "";
        if (count($this->values) != 0) {
            $yAxisGap = ($rect->getHeight()) / count($this->values);
        }

        foreach ($this->values as $cx => $cy) {
            $circle = new Circle();
            $circle->setCx($xAxisLabel + $rect->getX())
                ->setCy($rect->getHeight() + $rect->getY() - $cy)
                ->setRadius(2)
                ->setAttribute('fill', $this->getCircleColor());

            $x = $xAxisLabel + $rect->getX();
            $y = $rect->getHeight() + $rect->getY() - $cy;

            $points .= " $x,$y ";
            $polyline = new Polyline($points);
            $polyline->setColor('black');
            $points = " $x,$y ";
            $polyline->setAttribute('stroke', $polyline->getColor());
            $polyline->setAttribute('stroke-width', 0.2);

            $this->svg->addElement($circle);
            $this->svg->addElement($polyline);
            $xAxisLabel += $xAxisGap;
            $yAxisLabel -= $yAxisGap;
        }

        return $this->svg->render();
    }
}

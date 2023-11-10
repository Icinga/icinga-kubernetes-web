<?php

namespace Icinga\Module\Kubernetes\SVG\Graph;

use Icinga\Module\Kubernetes\SVG\Element\Polyline;
use Icinga\Module\Kubernetes\SVG\Element\Rect;
use Icinga\Module\Kubernetes\SVG\Palette;
use Icinga\Module\Kubernetes\SVG\SVG;

class BarGraph
{
    private $svg;

    private $values;

    private $warning;

    private $critical;

    public function setSvg($svg)
    {
        $this->svg = $svg;

        return $this;
    }

    public function getSvg()
    {
        return $this->svg;
    }

    public function setWarningThreshold($warning)
    {
        $this->warning = $warning;

        return $this;
    }

    public function getWarningThreshold()
    {
        return $this->warning;
    }

    public function setCriticalThreshold($critical)
    {
        $this->critical = $critical;

        return $this;
    }

    public function getCriticalThreshold()
    {
        return $this->critical;
    }

    public function __construct($values)
    {
        $this->values = $values;
    }

    public function assemble()
    {
        if ($this->getSvg() === null) {
            $this->setSvg(new SVG());
        }

        $barX = 0;
        $barWidth = 20;
        $maxValue = max($this->values);
        $paddingLeft = -10;
        $paddingRight = 10;
        $polyLineEndY = $barWidth * count($this->values);
        $polyLineEndX = $polyLineEndY + $paddingRight;

        if ($this->getWarningThreshold() !== null) {
            $polylineY = $maxValue - $this->getWarningThreshold();
            $warningLine = (new Polyline("$paddingLeft,$polylineY $polyLineEndX,$polylineY"));
            $warningLine->setAttribute('stroke', 'orange');
            $this->svg->addElement($warningLine);
        }

        if ($this->getCriticalThreshold() !== null) {
            $polylineY = $maxValue - $this->getCriticalThreshold();
            $criticalLine = (new Polyline("$paddingLeft,$polylineY $polyLineEndX,$polylineY"));
            $criticalLine->setAttribute('stroke', 'red');
            $this->svg->addElement($criticalLine);
        }

        foreach ($this->values as $_ => $value) {
            $bar = (new Rect())
                ->setX($barX)
                ->setY($maxValue - $value)
                ->setWidth($barWidth)
                ->setHeight($value);

            $color = new Palette();
            if (((($this->getWarningThreshold() !== null && $this->getCriticalThreshold() === null && $value >= $this->getWarningThreshold()))
                || (($this->getWarningThreshold() !== null && $this->getCriticalThreshold() !== null))
                && $value >= $this->getWarningThreshold() && $value < $this->getCriticalThreshold())) {
                $bar->setAttribute('fill', $color->getNext(Palette::WARNING));
            } elseif ((($this->getWarningThreshold() !== null && $this->getCriticalThreshold() !== null)
                    || ($this->getWarningThreshold() === null && $this->getCriticalThreshold() !== null))
                && $value >= $this->getCriticalThreshold()) {
                $bar->setAttribute('fill', $color->getNext(Palette::CRITICAL));
            } else {
                $bar->setAttribute('fill', $color->getNext(Palette::NEUTRAL));
            }
            $barX += $barWidth;

            $this->svg->addElement($bar);
        }

        return $this->svg->render();
    }
}

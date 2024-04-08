<?php

namespace Icinga\Module\Kubernetes\SVG\Graph;

use Icinga\Module\Kubernetes\SVG\Element\Rect;
use Icinga\Module\Kubernetes\SVG\Palette;
use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\HtmlElement;

class BarGraph
{
    private $dataset;

    private $barWidth = 3;

    private $warning;

    private $critical;

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

    public function __construct($dataset)
    {
        $this->dataset = $dataset;
    }

    private function drawSingleBar($point, $strokeWidth, $maxValue)
    {
        $y = $maxValue - $point[1];
        $rect = new Rect($point[0] - ($this->barWidth / 2), $y, $this->barWidth, $point[1]);
        $rect->setAttribute('stroke-color', 'white');
        $rect->setAttribute('stroke-width', $strokeWidth);

        return $rect;
    }

    public function toSvg(RenderContext $ctx)
    {
        $group = new HtmlElement('g');

        if (count($this->dataset) > 15) {
            $this->barWidth = 2;
        }
        if (count($this->dataset) > 25) {
            $this->barWidth = 1;
        }

        $maxValue = max(array_column($this->dataset, 1));

        foreach ($this->dataset as $_ => $point) {
            $color = new Palette();
            $bar = $this->drawSingleBar($point, 0, $maxValue)->toSvg($ctx);
            $bar->setAttribute('fill', 'white');
            $group->addHtml($bar);

            $bar = $this->drawSingleBar($point, 0, $maxValue)->toSvg($ctx);
            $group->addHtml($bar);

            if ($this->getWarningThreshold() !== null && $point[1] <= $this->getWarningThreshold()) {
                $bar->setAttribute('fill', $color->getNext(Palette::WARNING));
            }

            //TODO: critical threshold
        }

        return $group;
    }
}

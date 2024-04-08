<?php

namespace Icinga\Module\Kubernetes\SVG\Graph;

use Icinga\Module\Kubernetes\SVG\Axis;
use Icinga\Module\Kubernetes\SVG\Element\Circle;
use Icinga\Module\Kubernetes\SVG\Element\Path;
use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use Icinga\Module\Kubernetes\SVG\Unit\LinearUnit;
use ipl\Html\HtmlElement;

class LineGraph
{
    private $dataset;

    public function __construct(array $dataset)
    {
        usort($dataset, array($this, 'sortByX'));
        $this->dataset = $dataset;
    }

    private function sortByX(array $v1, array $v2)
    {
        if ($v1[0] === $v2[0]) {
            return 0;
        }

        return ($v1[0] < $v2[0]) ? -1 : 1;
    }

    public function addAxis()
    {
        $axis = new Axis();
        $axis->setUnitForXAxis(new LinearUnit())
             ->setUnitForYAxis(new LinearUnit());
        $axis->addDataset($this->dataset);

        return $axis;
    }

    public function toSvg(RenderContext $ctx)
    {
        $addAxis = new HtmlElement('div');
        $axis = $this->addAxis();
        $addAxis->addHtml($axis->toSvg($ctx));

        $group = new HtmlElement('g');

        $dataset = $this->dataset;
        $xMax = $ctx->xToAbsolute(max(array_column($this->dataset, 0)));
        $yMax = $ctx->yToAbsolute(max(array_column($this->dataset, 1)));
        $width = $ctx->getWidth();
        $height = $ctx->getHeight();

        if ($yMax > $height || $xMax > $width) {
            $scalingFactorY = $height / $yMax;
            $scalingFactorX = $width / $xMax;

            $i = 0;
            foreach ($this->dataset as $point) {
                $point[0] *= $scalingFactorX;
                $point[1] *= $scalingFactorY;

                $circle = new Circle($point[0], $point[1], 2);
                $circle->setAttribute('transform', 'translate(0, 1000) scale(1,-1)');

                $dataset[$i][0] = $point[0];
                $dataset[$i][1] = $point[1];
                $group->addHtml($circle->toSvg($ctx));
                $i++;
            }
        }

        $path = new Path($dataset);
        $path->setAttribute('transform', 'translate(0, 1000) scale(1,-1)');

        $group->addHtml($path->toSvg($ctx));
        $group->addHtml($axis->toSvg($ctx));

        return $group;
    }
}

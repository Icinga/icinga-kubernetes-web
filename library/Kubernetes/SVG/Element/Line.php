<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class Line extends BaseHtmlElement
{
    private $xStart;

    private $xEnd;

    private $yStart;

    private $yEnd;

    public function __construct($x1, $y1, $x2, $y2)
    {
        $this->xStart = $x1;
        $this->xEnd = $x2;
        $this->yStart = $y1;
        $this->yEnd = $y2;
    }

    public function toSvg(RenderContext $ctx)
    {
        list($x1, $y1) = $ctx->toAbsolute($this->xStart, $this->yStart);
        list($x2, $y2) = $ctx->toAbsolute($this->xEnd, $this->yEnd);

        $this->setAttribute('x1', $x1);
        $this->setAttribute('x2', $x2);
        $this->setAttribute('y1', $y1);
        $this->setAttribute('y2', $y2);

        return new HtmlElement('line', $this->getAttributes());
    }
}

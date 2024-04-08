<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class Circle extends BaseHtmlElement
{
    private $cy;

    private $r;

    public function __construct($cx, $cy, $r)
    {
        $this->cx = $cx;
        $this->cy = $cy;
        $this->r = $r;
    }

    public function toSvg(RenderContext $ctx)
    {
        $coords = $ctx->toAbsolute($this->cx, $this->cy);
        $this->setAttribute('cx', $coords[0]);
        $this->setAttribute('cy', $coords[1]);
        $this->setAttribute('r', $this->r);

        return new HtmlElement('circle', $this->getAttributes());
    }
}

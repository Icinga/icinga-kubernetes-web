<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class Polyline extends BaseHtmlElement
{
    private $points;

    public function __construct($points)
    {
        $this->points = $points;
    }

    public function toSvg(RenderContext $ctx)
    {
        $points = $ctx->toAbsolute($this->points[0], $this->points[1]);
        $this->setAttribute('points', $points);
        $this->setAttribute('stroke', '#ff5566');
        $this->setAttribute('fill', 'none');

        return new HtmlElement(
            'polyline',
            $this->getAttributes()
        );
    }
}

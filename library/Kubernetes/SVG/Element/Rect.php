<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class Rect extends BaseHtmlElement
{
    private $x;

    private $y;

    private $width;

    private $height;

    /**
     * Whether to keep the ratio
     *
     * @var bool
     */
    private $keepRatio = false;

    public function __construct($x, $y, $width, $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    public function toSvg(RenderContext $ctx)
    {
        list($x, $y) = $ctx->toAbsolute($this->x, $this->y);
        if ($this->keepRatio) {
            $ctx->keepRatio();
        }
        list($width, $height) = $ctx->toAbsolute($this->width, $this->height);
        if ($this->keepRatio) {
            $ctx->keepRatio();
        }

        $this->setAttribute('x', $x);
        $this->setAttribute('y', $y);
        $this->setAttribute('width', $width);
        $this->setAttribute('height', $height);

        return new HtmlElement('rect', $this->getAttributes());
    }
}

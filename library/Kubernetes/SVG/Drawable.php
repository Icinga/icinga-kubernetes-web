<?php

namespace Icinga\Module\Kubernetes\SVG;

use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\HtmlElement;

interface Drawable
{
    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx The context to use for rendering
     *
     * @return  HtmlElement         The SVG Element
     */
    public function toSvg(RenderSVG $svg);
}

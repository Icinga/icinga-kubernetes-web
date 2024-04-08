<?php

namespace Icinga\Module\Kubernetes\SVG;

use Icinga\Module\Kubernetes\SVG\Render\LayoutBox;
use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\HtmlElement;

class Canvas
{
    private $name;

    private $children = array();

    /**
     * @var LayoutBox
     */
    private $rect;

    public function __construct($name, LayoutBox $rect)
    {
        $this->name = $name;
        $this->rect = $rect;
    }

    public function getLayout()
    {
        return $this->rect;
    }

    /**
     * Add an element to this canvas
     *
     * @param Drawable $child
     */
    public function addElement(Drawable $child)
    {
        $this->children[] = $child;
    }

    public function toSvg(RenderContext $ctx)
    {
        $outer = $element = new HtmlElement('g');
        $innerContainer = new HtmlElement('g');
        $innerContainer->setAttribute('x', 0);
        $innerContainer->setAttribute('y', 0);
        $innerContainer->setAttribute('transform', $this->rect->getInnerTransform($ctx));

        $element->addHtml($innerContainer);

        foreach ($this->children as $child) {
            $innerContainer->addHtml($child->toSvg($ctx));
        }

        return $outer;
    }
}

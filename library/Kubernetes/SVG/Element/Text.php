<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;

class Text extends BaseHtmlElement
{
    /**
     * Align the text to end at the x and y position
     */
    public const ALIGN_END = 'end';

    /**
     * Align the text to start at the x and y position
     */
    public const ALIGN_START = 'start';

    /**
     * Align the text to be centered at the x and y position
     */
    public const ALIGN_MIDDLE = 'middle';

    private $x;

    private $y;

    private $text;

    private $fontsize;

    public function __construct($x, $y, $fontsize, $text)
    {
        $this->x = $x;
        $this->y = $y;
        $this->fontsize = $fontsize;
        $this->text = $text;
    }

    public function setFontSize($size)
    {
        $this->fontSize = $size;

        return $this;
    }

    public function toSvg(RenderContext $ctx)
    {
        list($x, $y) = $ctx->toAbsolute($this->x, $this->y);
        $this->setAttribute('x', $x);
        $this->setAttribute('y', $y);
        $this->setAttribute('font-size', $this->fontsize);

        return new HtmlElement(
            'text',
            $this->getAttributes(),
            new HtmlString($this->text)
        );
    }
}

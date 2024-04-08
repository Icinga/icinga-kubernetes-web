<?php

namespace Icinga\Module\Kubernetes\SVG;

use Icinga\Module\Kubernetes\SVG\Render\LayoutBox;
use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;

class RenderSVG extends HtmlDocument
{
    public const X_ASPECT_RATIO_MIN = 'xMin';

    public const X_ASPECT_RATIO_MID = 'xMid';

    public const X_ASPECT_RATIO_MAX = 'xMax';

    public const Y_ASPECT_RATIO_MIN = 'YMin';

    public const Y_ASPECT_RATIO_MID = 'yMid';

    public const Y_ASPECT_RATIO_MAX = 'yMax';

    public const ASPECT_RATIO_PAD = 'meet';

    public const ASPECT_RATIO_CUTOFF = 'slice';

    /** @var HtmlDocument */
    private $document;

    /** @var HtmlElement */
    private $svg;

    private $width;

    private $height;

    /**
     * @var ValidHtml[]
     */
    private $contents = [];

    protected $attributes = [];

    private $preserveAspectRatio = true;

    /**
     * Horizontal alignment of SVG element
     *
     * @var string
     */
    private $xAspectRatio = self::X_ASPECT_RATIO_MIN;

    /**
     * Vertical alignment of SVG element
     *
     * @var string
     */
    private $yAspectRatio = self::Y_ASPECT_RATIO_MIN;

    /**
     * Define whether aspect differences should be handled using padding (default) or cutoff
     *
     * @var string
     */
    private $xFillMode = "meet";

    /**
     * @var Canvas
     */
    private $rootCanvas;

    public function createRootDocument()
    {
        $this->svg = $this->createOuterBox();
    }

    public function createOuterBox()
    {
        $ctx = $this->createRenderContext();
        $svg = new HtmlElement(
            'svg',
            Attributes::create([
                'xmlns'   => 'http://www.w3.org/2000/svg',
                'width'   => '100%',
                'height'  => '100%',
                'viewbox' =>
                    sprintf(
                        '0 0 %s %s',
                        $ctx->getNrOfUnitsX(),
                        $ctx->getNrOfUnitsY()
                    )
            ])
        );

        if ($this->preserveAspectRatio) {
            $svg->setAttribute(
                'preserveAspectRatio',
                sprintf(
                    '%s%s %s',
                    $this->xAspectRatio,
                    $this->yAspectRatio,
                    $this->xFillMode
                )
            );
        }

        return $svg;
    }

    public function addElement(HtmlElement $element)
    {
        $this->contents[] = $element;
    }

    public function getElements()
    {
        foreach ($this->contents as $content) {
            $element[] = $content;
        }

        return $element;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function __construct(HtmlDocument $document, $width = 1000, $height = 1000)
    {
        $this->document = $document;
        $this->width = $width;
        $this->height = $height;
        $this->rootCanvas = new Canvas('root', new LayoutBox(0, 0));
    }

    public function render()
    {
        $this->createRootDocument();
        $ctx = $this->createRenderContext();
        $this->svg->addHtml($this->rootCanvas->toSvg($ctx));
        foreach ($this->getElements() as $element) {
            $this->svg->addHtml($element);
        }

        return $this->svg;
    }

    public function createRenderContext()
    {
        return new RenderContext($this->document, $this->width, $this->height);
    }

    /**
     * Preserve the aspect ratio of the rendered object
     *
     * Do not deform the content of the SVG when the aspect ratio of the viewBox
     * differs from the aspect ratio of the SVG element, but add padding or cutoff
     * instead
     *
     * @param bool $preserve Whether the aspect ratio should be preserved
     */
    public function preserveAspectRatio($preserve = true)
    {
        $this->preserveAspectRatio = $preserve;
    }

    /**
     * Change the horizontal alignment of the SVG element
     *
     * Change the horizontal alignment of the svg, when preserveAspectRatio is used and
     * padding is present. Defaults to
     */
    public function setXAspectRatioAlignment($alignment)
    {
        $this->xAspectRatio = $alignment;
    }

    /**
     * Change the vertical alignment of the SVG element
     *
     * Change the vertical alignment of the svg, when preserveAspectRatio is used and
     * padding is present.
     */
    public function setYAspectRatioAlignment($alignment)
    {
        $this->yAspectRatio = $alignment;
    }

    public function getCanvas()
    {
        return $this->rootCanvas;
    }
}

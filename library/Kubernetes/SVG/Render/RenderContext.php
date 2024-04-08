<?php

namespace Icinga\Module\Kubernetes\SVG\Render;

use ipl\Html\HtmlDocument;

/**
 * Context for rendering, handles ratio based coordinate calculations.
 *
 * The most important functions when rendering are the toAbsolute and roRelative
 * values, taking world coordinates and translating them into local coordinates.
 */
class RenderContext
{
    /**
     * The base size of the viewport, i.e. how many units are available on a 1:1 ratio
     *
     * @var array
     */
    private $viewBoxSize = array(1000, 1000);

    /**
     * The HtmlDocument for modifying the elements
     *
     * @var HtmlDocument
     */
    private $document;

    private $width;

    private $height;

    /**
     * If true no ratio correction will be made
     *
     * @var bool
     */
    private $respectRatio = false;

    /**
     * The ratio on the x side. A x ration of 2 means that the width of the SVG is divided in 2000
     * units (see $viewBox)
     *
     * @var int
     */
    private $xratio = 1;

    /**
     * The ratio on the y side. A y ration of 2 means that the height of the SVG is divided in 2000
     * units (see $viewBox)
     *
     * @var int
     */
    private $yratio = 1;

    /**
     * Creates a new context for the given HTML Document
     *
     * @param HtmlDocument   $document   The HTML document represented by this context
     * @param int $width      The width (may be approximate) of the document
     *                                  (only required for ratio calculation)
     * @param int           $height     The height (may be approximate) of the document
     *                                  (only required for ratio calculation)
     */
    public function __construct(HtmlDocument $document, int $width = 2000, int $height = 1000)
    {
        $this->document = $document;
        $this->width = $width;
        $this->height = $height;

        if ($width > $height) {
            $this->xratio = $width / $height;
        } elseif ($height > $width) {
            $this->yratio = $height / $width;
        }
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function keepRatio()
    {
        $this->respectRatio = true;
    }

    public function ignoreRatio()
    {
        $this->respectRatio = false;
    }

    public function getNrOfUnitsY()
    {
        return intval($this->viewBoxSize[1] * $this->yratio);
    }

    public function getNrOfUnitsX()
    {
        return intval($this->viewBoxSize[0] * $this->xratio);
    }

    /**
     * Transforms the x,y coordinate from relative coordinates to absolute world coordinates
     *
     * (50, 50) would be a point in the middle of the document and map to 500, 1000 on a
     * 1000 x 1000 viewbox with a 1:2 ratio.
     *
     * @param   int $x  The relative x coordinate
     * @param   int $y  The relative y coordinate
     *
     * @return  array   An x,y tuple containing absolute coordinates
     * @see     RenderContext::toRelative
     */
    public function toAbsolute($x, $y)
    {
        return array($this->xToAbsolute($x), $this->yToAbsolute($y));
    }

    /**
     * Transforms the x,y coordinate from absolute coordinates to relative world coordinates
     *
     * This is the inverse function of toAbsolute
     *
     * @param   int $x  The absolute x coordinate
     * @param   int $y  The absolute y coordinate
     *
     * @return  array   An x,y tupel containing absolute coordinates
     * @see     RenderContext::toAbsolute
     */
    public function toRelative($x, $y)
    {
        return array($this->xToRelative($x), $this->yToRelative($y));
    }

    /**
     * Calculates the scale transformation required to apply the padding on an Canvas
     *
     * @param   array $padding  A 4 element array containing top, right, bottom and left padding
     *
     * @return  array           An array containing the x and y scale
     */
    public function paddingToScaleFactor(array $padding)
    {
        list($horizontalPadding, $verticalPadding) = $this->toAbsolute(
            $padding[LayoutBox::PADDING_RIGHT] + $padding[LayoutBox::PADDING_LEFT],
            $padding[LayoutBox::PADDING_TOP] + $padding[LayoutBox::PADDING_BOTTOM]
        );

        return array(
            ($this->getNrOfUnitsX() - $horizontalPadding) / $this->getNrOfUnitsX(),
            ($this->getNrOfUnitsY() - $verticalPadding) / $this->getNrOfUnitsY()
        );
    }

    /**
     * Transform a relative x coordinate to an absolute one
     *
     * @param   int $x  A relative x coordinate
     *
     * @return  int     An absolute x coordinate
     **/
    public function xToAbsolute($x)
    {
        return $this->getNrOfUnitsX() / 100 * $x / ($this->respectRatio ? $this->xratio : 1);
    }

    /**
     * Transform a relative y coordinate to an absolute one
     *
     * @param   int $y  A relative y coordinate
     *
     * @return  int     An absolute y coordinate
     */
    public function yToAbsolute($y)
    {
        return $this->getNrOfUnitsY() / 100 * $y / ($this->respectRatio ? $this->yratio : 1);
    }

    /**
     * Transform an absolute x coordinate to a relative one
     *
     * @param   int $x  An absolute x coordinate
     *
     * @return  int     A relative x coordinate
     */
    public function xToRelative($x)
    {
        return $x / $this->getNrOfUnitsX() * 100  * ($this->respectRatio ? $this->xratio : 1);
    }

    /**
     * Transform a absolute y coordinate to an relative one
     *
     * @param   int $y  An absolute x coordinate
     *
     * @return  int     A relative x coordinate
     */
    public function yToRelative($y)
    {
        return $y / $this->getNrOfUnitsY() * 100 * ($this->respectRatio ? $this->yratio : 1);
    }
}

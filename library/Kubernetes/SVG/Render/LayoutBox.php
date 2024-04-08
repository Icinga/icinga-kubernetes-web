<?php

namespace Icinga\Module\Kubernetes\SVG\Render;

use Icinga\Module\Kubernetes\SVG\Format;

class LayoutBox
{
    public const PADDING_TOP = 0;

    public const PADDING_RIGHT = 1;

    public const PADDING_BOTTOM = 2;

    public const PADDING_LEFT = 3;
    private $height;

    private $width;

    private $x;

    private $y;

    private $padding = array(0, 0, 0, 0);

    public function __construct($x, $y, $width = null, $height = null)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width ? : 100;
        $this->height = $height ? : 100;
    }

    public function getInnerTransform(RenderContext $ctx)
    {
        list($translateX, $translateY) = $ctx->toAbsolute(
            $this->padding[self::PADDING_LEFT] + $this->getX(),
            $this->padding[self::PADDING_TOP] + $this->getY()
        );
        list($scaleX, $scaleY) = $ctx->paddingToScaleFactor($this->padding);

        $scaleX *= $this->getWidth() / 100;
        $scaleY *= $this->getHeight() / 100;

        return sprintf(
            'translate(%s, %s) scale(%s, %s)',
            Format::formatSVGNumber($translateX),
            Format::formatSVGNumber($translateY),
            Format::formatSVGNumber($scaleX),
            Format::formatSVGNumber($scaleY)
        );
    }

    public function getPadding()
    {
        return $this->padding;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }
}

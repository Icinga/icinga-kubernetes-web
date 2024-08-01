<?php

namespace Icinga\Module\Kubernetes\SVG\Element;

use Icinga\Module\Kubernetes\SVG\Render\RenderContext;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class Path extends BaseHtmlElement
{
    protected const TPL_MOVE = 'M %s %s';

    protected const TPL_LINE = 'L %s %s';

    /**
     * True to treat coordinates as absolute values
     *
     * @var bool
     */
    protected $isAbsolute = false;

    /**
     * The points to draw, in the order they are drawn
     *
     * @var array
     */
    protected $points = array();

    /**
     * True to draw the path discrete, i.e. make hard steps between points
     *
     * @var bool
     */
    protected $discrete = false;

    public function __construct(array $points)
    {
        $this->append($points);
    }

    public function append(array $points)
    {
        if (count($points) === 0) {
            return $this;
        }
        if (! is_array($points[0])) {
            $points = array($points);
        }
        $this->points = array_merge($this->points, $points);

        return $this;
    }

    public function toSvg(RenderContext $ctx)
    {
        $pathDescription = '';
        $tpl = self::TPL_MOVE;
        $lastPoint = null;

        foreach ($this->points as $point) {
            if (! $this->isAbsolute) {
                $point = $ctx->toAbsolute($point[0], $point[1]);
            }

            if ($lastPoint && $this->discrete) {
                $pathDescription .= sprintf($tpl, $point[0], $lastPoint[1]);
            }
            $pathDescription .= vsprintf($tpl, $point);
            $lastPoint = $point;
            $tpl = self::TPL_LINE;
        }

        $this->setAttribute('d', $pathDescription);
        $this->setAttribute('fill', 'none');
        $this->setAttribute('stroke', '#ff5566');
        $this->setAttribute('stroke-width', 2);

        return new HtmlElement('g', null, new HtmlElement(
            'path',
            $this->getAttributes()
        ));
    }
}

<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class LineChart extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes;

    /** @var string  */
    protected $values;

    /** @var string  */
    protected $labels;

    /** @var string  */
    protected $label;

    /** @var string  */
    protected $color;

    public function __construct(string $chartSizeClass, string $values, string $labels, string $label, string $color)
    {
        $this->defaultAttributes['class'] = $chartSizeClass;
        $this->values = $values;
        $this->labels = $labels;
        $this->label = $label;
        $this->color = $color;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new HtmlElement(
                'canvas',
                new Attributes(
                    [
                        'class' => 'line-chart',
                        'data-values' => $this->values,
                        'data-labels' => $this->labels,
                        'data-label' => $this->label,
                        'data-color' => $this->color
                    ]
                )
            )
        );
    }
}

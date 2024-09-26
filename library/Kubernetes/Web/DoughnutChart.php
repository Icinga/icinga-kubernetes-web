<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class DoughnutChart extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes;

    protected string $values;

    protected string $labels;

    protected string $colors;

    public function __construct(string $chartSizeClass, string $values, string $labels, string $colors)
    {
        $this->defaultAttributes['class'] = $chartSizeClass;
        $this->values = $values;
        $this->labels = $labels;
        $this->colors = $colors;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new HtmlElement(
                'canvas',
                new Attributes(
                    [
                        'class' => 'doughnut-chart',
                        'data-values' => $this->values,
                        'data-labels' => $this->labels,
                        'data-colors' => $this->colors
                    ]
                )
            )
        );
    }
}

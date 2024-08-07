<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\Attributes;

class DoughnutChart extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes;

    /** @var string  */
    protected $values;

    /** @var string  */
    protected $labels;

    /** @var string  */
    protected $colors;

    public function __construct(string $chartSizeClass, string $values, string $labels, string $colors)
    {
        $this->defaultAttributes['class'] = $chartSizeClass;
        $this->values = $values;
        $this->labels = $labels;
        $this->colors = $colors;
    }

    protected function assemble()
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

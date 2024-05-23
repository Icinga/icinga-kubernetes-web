<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\Attributes;

class DoughnutChartStates extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes;

    protected string $value;

    protected string $label;

    protected string $colors;

    public function __construct(string $chartSizeClass, string $value, string $label, string $colors)
    {
        $this->defaultAttributes['class'] = $chartSizeClass;
        $this->value = $value;
        $this->label = $label;
        $this->colors = $colors;
    }

    protected function assemble()
    {
        $this->addHtml(
            new HtmlElement(
                'canvas',
                new Attributes(
                    [
                        'class' => 'doughnut-chart-states',
                        'data-value' => $this->value,
                        'data-label' => $this->label,
                        'data-colors' => $this->colors
                    ]
                )
            )
        );
    }
}
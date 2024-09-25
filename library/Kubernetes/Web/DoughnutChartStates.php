<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class DoughnutChartStates extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes;

    /** @var string  */
    protected $value;

    /** @var string  */
    protected $label;

    /** @var string  */
    protected $colors;

    public function __construct(string $chartSizeClass, string $value, string $label, string $colors)
    {
        $this->defaultAttributes['class'] = $chartSizeClass;
        $this->value = $value;
        $this->label = $label;
        $this->colors = $colors;
    }

    protected function assemble(): void
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

<?php

namespace Icinga\Module\Kubernetes\Web\Widget\Chart;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class DoughnutChartRequestLimit extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes;

    protected string $request;

    protected string $limit;

    protected string $real;

    protected string $realColor;

    public function __construct(string $chartSizeClass, string $request, string $limit, string $real, string $realColor)
    {
        $this->defaultAttributes['class'] = $chartSizeClass;
        $this->request = $request;
        $this->limit = $limit;
        $this->real = $real;
        $this->realColor = $realColor;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new HtmlElement(
                'canvas',
                new Attributes(
                    [
                        'class' => 'doughnut-chart-request-limit',
                        'data-request' => $this->request,
                        'data-limit' => $this->limit,
                        'data-real' => $this->real,
                        'data-real-color' => $this->realColor
                    ]
                )
            )
        );
    }
}

<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class DoughnutChartRequestLimit extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes;

    /** @var string  */
    protected string $request;

    /** @var string  */
    protected string $limit;

    /** @var string  */
    protected string $real;

    /** @var string  */
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

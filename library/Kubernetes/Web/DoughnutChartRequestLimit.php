<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\Attributes;

class DoughnutChartRequestLimit extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes;

    /** @var string  */
    protected $request;

    /** @var string  */
    protected $limit;

    /** @var string  */
    protected $real;

    /** @var string  */
    protected $realColor;

    public function __construct(string $chartSizeClass, string $request, string $limit, string $real, string $realColor)
    {
        $this->defaultAttributes['class'] = $chartSizeClass;
        $this->request = $request;
        $this->limit = $limit;
        $this->real = $real;
        $this->realColor = $realColor;
    }

    protected function assemble()
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

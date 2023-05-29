<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class Dashboard extends BaseHtmlElement
{
    protected $defaultAttributes = [
        'class' => 'kubernetes-dashboard'
    ];

    protected $tag = 'div';

    public function assemble()
    {
        $this->addHtml(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text('Nodes')),
            new HtmlElement(
                'ul',
                new Attributes(['id' => 'grid']),
                new HtmlElement('li', null, new HtmlElement('div', new Attributes(['class' => 'hexagon']))),
                new HtmlElement('li', null, new HtmlElement('div', new Attributes(['class' => 'hexagon'])))
            )
        ));

//        $chart = new GridChart();
//        $chart->setAxisLabel("X axis label", "Y axis label");
//        $chart->setXAxis(Axis::CalendarUnit());
//        $chart->drawLines(
//            [
//                'data' => [
//                    [time() - 7200, 10],
//                    [time() - 3620, 30],
//                    [time() - 1800, 15],
//                    [time(), 92]
//                ]
//            ]
//        );
//        $this->addHtml(new HtmlElement('div', null, new HtmlString($chart->render())));
    }
}

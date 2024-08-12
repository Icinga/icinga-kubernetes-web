<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;

class MetricCharts extends BaseHtmlElement
{
    use Translation;

    protected $defaultAttributes = ['class' => 'metric-charts'];

    /** @var array */
    protected $chartRows;

    protected $tag = 'section';

    public function __construct(array ...$chartRows)
    {
        $this->chartRows = $chartRows;
    }

    protected function assemble(): void
    {
        foreach ($this->chartRows as $row) {
            $rowElement = new HtmlElement('div', new Attributes(['class' => 'metric-charts-row']));
            foreach ($row as $chart) {
                $rowElement->addHtml($chart);
            }
            if (! $rowElement->isEmpty()) {
                $this->addHtml($rowElement);
            }
        }

        if (! $this->isEmpty()) {
            $this->prepend(new HtmlElement('h2', null, new Text($this->translate('Metric Charts'))));
        }
    }
}

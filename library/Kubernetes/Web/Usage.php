<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;

class Usage extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'progress-bar dont-print', 'style' => 'width: 5em; margin-right: 0.5em;'];

    protected $used;

    protected $available;

    const GRANULARITY = 10;

    public function __construct($used, $available)
    {
        $this->used = $used;
        $this->available = $available;
    }

    protected function assemble()
    {
        $ratio = ceil($this->used / $this->available * 100 / static::GRANULARITY) * static::GRANULARITY;

        if ($ratio >= 75) {
            if ($ratio >= 90) {
                $state = 'state-critical';
            } else {
                $state = 'state-warning';
            }
        } else {
            $state = 'state-ok';
        }

        $this->add(
            Html::tag(
                'div',
                ['style' => sprintf('width: %.2F%%;', $ratio), 'class' => "bg-stateful {$state}"],
                new HtmlString('&nbsp;')
            )
        );
    }
}

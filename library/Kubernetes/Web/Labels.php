<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;

class Labels extends BaseHtmlElement
{
    protected $labels;

    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'labels'];

    public function __construct(iterable $labels)
    {
        $this->labels = $labels;
    }

    protected function assemble()
    {
        $this->addHtml(new HtmlElement('h2', null, new Text(t('Labels'))));

        foreach ($this->labels as $label) {
            $this->addHtml(new HorizontalKeyValue($label->name, $label->value));
        }
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;

class Labels extends BaseHtmlElement
{
    use Translation;

    protected $defaultAttributes = ['class' => 'labels'];

    protected $labels;

    protected $tag = 'section';

    public function __construct(iterable $labels)
    {
        $this->labels = $labels;
    }

    protected function assemble()
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Labels'))));

        foreach ($this->labels as $label) {
            $this->addHtml(new HorizontalKeyValue($label->name, $label->value));
        }
    }
}

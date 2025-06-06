<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;

class Details extends BaseHtmlElement
{
    use Translation;

    protected $defaultAttributes = ['class' => 'details'];

    protected iterable $details;

    protected $tag = 'section';

    public function __construct(iterable $details)
    {
        $this->details = $details;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Details'))));

        foreach ($this->details as $key => $value) {
            $this->addHtml(new HorizontalKeyValue($key, $value));
        }
    }
}

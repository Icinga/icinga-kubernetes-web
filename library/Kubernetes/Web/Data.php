<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\EmptyState;
use Iterator;

class Data extends BaseHtmlElement
{
    use Translation;

    /** @var Iterator */
    protected $data;

    protected $tag = 'section';

    public function __construct(Iterator $data)
    {
        $this->data = $data;
    }

    protected function assemble()
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Data'))));

        if (! $this->data->valid()) {
            // TODO(el): Is this even possible?
            $this->addHtml(new EmptyState($this->translate('No data to display.')));

            return;
        }

        foreach ($this->data as $data) {
            $this->addHtml(new HtmlElement(
                'div',
                new Attributes([
                    'class'               => 'collapsible',
                    'data-visible-height' => 100
                ]),
                new HtmlElement('h3', null, new Text($data->name)),
                new HtmlElement('pre', null, new Text($data->value))
            ));
        }
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\EmptyState;

class Data extends BaseHtmlElement
{
    use Translation;

    protected iterable $data;

    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'data'];

    public function __construct(iterable $data)
    {
        $this->data = $data;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Data'))));

        $data = yield_iterable($this->data);
        if ($data->valid()) {
            foreach ($data as $item) {
                $this->addHtml(new HtmlElement(
                    'div',
                    new Attributes([
                        'class'               => 'collapsible',
                        'data-visible-height' => 100
                    ]),
                    new HtmlElement('h3', null, new Text($item->name)),
                    new HtmlElement('pre', null, new Text($item->value))
                ));
            }
        } else {
            $this->addHtml(new EmptyState($this->translate('No items to display.')));
        }
    }
}

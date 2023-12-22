<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\EmptyState;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use Iterator;

class Data extends BaseHtmlElement
{
    protected $tag = 'section';

    /** @var Iterator */
    protected $data;

    public function __construct(Iterator $data)
    {
        $this->data = $data;
    }

    protected function assemble()
    {
        $this->addHtml(new HtmlElement('h2', null, new Text(t('Data'))));

        if (! $this->data->valid()) {
            // TODO(el): Is this even possible?
            $this->addHtml(new EmptyState(t('No data to display')));

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

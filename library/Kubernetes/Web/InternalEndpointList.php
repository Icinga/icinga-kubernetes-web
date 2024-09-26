<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;

class InternalEndpointList extends BaseHtmlElement
{
    use Translation;

    protected iterable $endpoints;

    protected $tag = 'ul';

    public function __construct(iterable $endpoints)
    {
        $this->endpoints = $endpoints;
    }

    protected function assemble(): void
    {
        $this->addWrapper(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text($this->translate('Internal Endpoints')))
        ));

        foreach ($this->endpoints as $endpoint) {
            foreach ($endpoint as $key => $value) {
                if ($key === 'port' || $key === 'target_port' || $key === 'node_port') {
                    $this->addHtml(new HtmlElement('li', null, FormattedString::create('%s: %s', $key, $value)));
                }
            }
        }
    }
}

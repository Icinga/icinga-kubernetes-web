<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class InternalEndpointList extends BaseHtmlElement
{
    protected $endpoints;

    protected $tag = 'ul';

    public function __construct(iterable $endpoints)
    {
        $this->endpoints = $endpoints;
    }

    protected function assemble()
    {
        $this->addWrapper(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text(t('Internal Endpoints')))
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

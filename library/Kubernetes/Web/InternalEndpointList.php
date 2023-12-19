<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class InternalEndpointList extends BaseHtmlElement
{
    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'internal-endpoint-list'];

    protected $endpoints;

    public function __construct(iterable $endpoints)
    {
        $this->endpoints = $endpoints;
    }

    protected function assemble()
    {
        $this->addHtml(new HtmlElement('h2', null, new Text(t('Internal Endpoints'))));

        foreach ($this->endpoints as $endpoint) {
            foreach ($endpoint as $key => $value) {
                if ($key === 'port' || $key === 'target_port' || $key === 'node_port') {
                    $this->addHtml(new HtmlElement('li', null, new Text($key . ": " .  $value)));
                }
            }
        }
    }
}

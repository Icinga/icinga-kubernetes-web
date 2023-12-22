<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;

class EndpointTable extends Table
{
    protected $columnDefinitions;

    protected $resource;

    protected $defaultAttributes = [
        'class' => 'common-table collapsible'
    ];

    public function __construct($resource, array $columnDefinitions)
    {
        $this->resource = $resource;
        $this->columnDefinitions = $columnDefinitions;
    }

    public function assemble()
    {
        $this->addWrapper(
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text(t('Endpoints')))
            )
        );

        $header = new HtmlElement('tr');
        foreach ($this->columnDefinitions as $label) {
            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
        }
        $this->getHeader()->addHtml($header);

        foreach ($this->resource as $resource) {
            $row = new HtmlElement('tr');
            foreach ($this->columnDefinitions as $column => $_) {
                $content = Text::create($resource->$column);
                $row->addHtml(new HtmlElement('td', null, $content));
            }
            $this->addHtml($row);
        }
    }
}

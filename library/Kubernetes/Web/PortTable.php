<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Icons;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\I18n\Translation;

class PortTable extends Table
{
    use Translation;

    protected $columnDefinitions;

    protected $defaultAttributes = [
        'class' => 'common-table collapsible'
    ];

    protected $resource;

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
                new HtmlElement('h2', null, new Text($this->translate('Ports')))
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
                $value = $resource->$column;
                if (is_bool($value)) {
                    $content = Icons::ready($value);
                } else {
                    $content = Text::create($value);
                }
                $row->addHtml(new HtmlElement('td', null, $content));
            }
            $this->addHtml($row);
        }
    }
}

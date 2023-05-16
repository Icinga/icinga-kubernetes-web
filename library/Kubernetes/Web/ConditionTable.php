<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class ConditionTable extends Table
{
    protected $defaultAttributes = [
        'class' => 'condition-table collapsible'
    ];

    protected $columnDefinitions;

    protected $resource;

    public function __construct($resource, array $columnDefinitions)
    {
        $this->columnDefinitions = $columnDefinitions;
        $this->resource = $resource;
    }

    public function assemble()
    {
        $header = new HtmlElement('tr');
        foreach ($this->columnDefinitions as $label) {
            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
        }
        $this->getHeader()->addHtml($header);

        foreach ($this->resource->condition as $condition) {
            $row = new HtmlElement('tr');
            foreach ($this->columnDefinitions as $column => $_) {
                if (
                    $column === 'last_probe'
                    || $column === 'last_transition'
                    || $column === 'last_update'
                ) {
                    $content = new TimeAgo($condition->$column->getTimestamp());
                } else {
                    $content = Text::create($condition->$column);
                }
                $row->addHtml(new HtmlElement('td', null, $content));
            }
            $this->addHtml($row);
        }

        $this->addWrapper(new HtmlElement(
            'section',
            new Attributes(['class' => 'conditions']),
            new HtmlElement('h2', null, new Text(t('Conditions')))
        ));
    }
}

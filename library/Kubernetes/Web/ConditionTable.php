<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use DateTimeInterface;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\TimeAgo;

class ConditionTable extends Table
{
    use Translation;

    protected array $columnDefinitions;

    protected $defaultAttributes = [
        'class' => 'condition-table collapsible'
    ];

    protected $resource;

    public function __construct($resource, array $columnDefinitions)
    {
        $this->columnDefinitions = $columnDefinitions;
        $this->resource = $resource;
    }

    protected function assemble(): void
    {
        $conditions = $this->resource->condition->execute();
        if (! $conditions->valid()) {
            return;
        }

        $header = new HtmlElement('tr');
        foreach ($this->columnDefinitions as $label) {
            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
        }
        $this->getHeader()->addHtml($header);

        foreach ($conditions as $condition) {
            $row = new HtmlElement('tr');
            foreach ($this->columnDefinitions as $column => $_) {
                if (
                    $column === 'last_probe'
                    || $column === 'last_transition'
                    || $column === 'last_update'
                    || $column === 'last_heartbeat'
                ) {
                    if ($condition->$column instanceof DateTimeInterface) {
                        $content = new TimeAgo($condition->$column->getTimestamp());
                    } else {
                        $content = Text::create('-');
                    }
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
            new HtmlElement('h2', null, new Text($this->translate('Conditions')))
        ));
    }
}

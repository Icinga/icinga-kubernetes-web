<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodCondition;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class PodConditionTable extends Table
{
    protected $defaultAttributes = [
        'class' => 'condition-table collapsible'
    ];

    /** @var Pod $pod */
    protected $pod;

    public function __construct(Pod $pod)
    {
        $this->pod = $pod;
    }

    public function assemble()
    {
        $columns = (new PodCondition())->getColumnDefinitions();

        $header = new HtmlElement('tr');
        foreach ($columns as $label) {
            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
        }
        $this->getHeader()->addHtml($header);

        /** @var PodCondition $condition */
        foreach ($this->pod->condition as $condition) {
            $row = new HtmlElement('tr');
            foreach ($columns as $column => $_) {
                if ($column === 'last_probe' || $column === 'last_transition') {
                    $content = new TimeAgo($condition->$column->getTimestamp());
                } else {
                    $content = Text::create($condition->$column);
                }
                $row->addHtml(new HtmlElement('td', null, $content));
            }
            $this->addHtml($row);
        }
    }
}

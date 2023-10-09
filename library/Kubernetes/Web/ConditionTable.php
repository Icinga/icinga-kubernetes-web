<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\TimeAgo;
use Predis\Command\Argument\TimeSeries\AddArguments;

class ConditionTable extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'condition-table'
    ];

    public const CONDITIONS = [
        'Initialized',
        'PodScheduled',
        'ContainersReady',
        'Ready',
    ];

    protected $message;

    protected $columnDefinitions;

    protected $resource;

    protected $columns;

    public function __construct($resource, array $columnDefinitions)
    {
        $this->columnDefinitions = $columnDefinitions;
        $this->resource = $resource;
    }

    public function addColumn(... $content)
    {
        $html = new HtmlElement('div', Attributes::create( ['class' => 'column']), ... $content);
        $this->columns->add($html);
    }

    public function assemble()
    {
        $this->columns = new HtmlElement('div', new Attributes(['class' => 'columns']));
        $last_state = false;
        $icon = '';

        foreach (self::CONDITIONS as $condition) {
            $columnClasses = [];

            foreach($this->resource->condition as $c) {
                if ($condition == $c->type) {
                    $state = $c->status;

                    if($c->status === 'True') {
                        $icon = (new Icon('circle-check'))->setStyle('fa-regular');
                    } else {
                        if ($last_state) {
                            $icon = (new Icon('circle'))->setStyle('fa-regular');
                            $state = 'neutral';
                        } else {
                            $icon = (new Icon('circle-xmark'))->setStyle('fa-solid');
                            $this->message = 'Maecenas sed diam eget risus varius blandit sit amet non magna. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.';
                            $last_state = true;
                        }
                    }

                    $columnClasses []= strtolower($state);
                }
            }

            $this->columns->add(HtmlElement::create('div',
                ['class' => array_merge(['column'], $columnClasses)],
                [
                    $icon,
                    new HtmlElement('div', null, new Text(implode(' ', preg_split('/(?=[A-Z])/', $condition)))),
                    new TimeAgo($c->last_transition->getTimestamp())
                ]
            ));
        }

        $this->add($this->columns);
        if (!empty($this->message)) {
            $this->add(new HtmlElement('div', new Attributes(['class' => 'message preformatted']), new Text($this->message)));
        }

//        $header = new HtmlElement('tr');
//        foreach ($this->columnDefinitions as $label) {
//            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
//        }
//        $this->getHeader()->addHtml($header);
//
//        foreach ($this->resource->condition as $condition) {
//            $row = new HtmlElement('tr');
//            foreach ($this->columnDefinitions as $column => $_) {
//                if (
//                    $column === 'last_probe'
//                    || $column === 'last_transition'
//                    || $column === 'last_update'
//                    || $column === 'last_heartbeat'
//		) {
//		    if ($condition->$column instanceof \DateTime) {
//			    $content = new TimeAgo($condition->$column->getTimestamp());
//		     } else {
//                    $content = Text::create('-');
//		     }
//                } else {
//                    $content = Text::create($condition->$column);
//                }
//                $row->addHtml(new HtmlElement('td', null, $content));
//            }
//            $this->addHtml($row);
//        }
//
//        $this->addWrapper(new HtmlElement(
//            'section',
//            new Attributes(['class' => 'conditions']),
//            new HtmlElement('h2', null, new Text(t('Conditions')))
//        ));
    }
}

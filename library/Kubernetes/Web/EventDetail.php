<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Events\Model\Event;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class EventDetail extends BaseHtmlElement
{
    /** @var Event */
    private $event;

    protected $tag = 'ul';

    public function __construct($event)
    {
        $this->event = $event;
    }

    protected function assemble()
    {
        $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
            new Text(sprintf('UID: %s', $this->event->uid))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
            new Text(sprintf('Namespace/Name: %s/%s', $this->event->namespace, $this->event->name))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
            new Text(sprintf('Type: %s', $this->event->type))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
            new Text(sprintf('Reason: %s', $this->event->reason))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
            new Text(sprintf('Note: %s', $this->event->note))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
            new Text(sprintf('Reference Object: %s - %s', $this->event->reference_kind, $this->event->reference))));

        if ($this->event->reporting_controller) {
            $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
                new Text(sprintf('Reporting Controller: %s', $this->event->reporting_controller))));
        }

        if ($this->event->reporting_instance) {
            $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
                new Text(sprintf('Reporting Controller: %s', $this->event->reporting_instance))));
        }

        $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
            new Text(sprintf('Action: %s', $this->event->action))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'event-detail-item']),
            new Text('Created: '), new TimeAgo($this->event->created->getTimestamp())));
    }
}
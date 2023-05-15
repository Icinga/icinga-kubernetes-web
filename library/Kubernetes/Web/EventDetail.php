<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Events\Model\Event;
use Icinga\Module\Icingadb\Util\PluginOutput;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class EventDetail extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'event-detail',
    ];

    /** @var Event */
    protected $event;


    public function __construct($event)
    {
        $this->event = $event;
    }

    protected function assemble()
    {
        $this->addHtml(new Details([
            t('Name')                 => $this->event->name,
            t('Namespace')            => $this->event->namespace,
            t('UID')                  => $this->event->uid,
            t('First Seen')           => new TimeAgo($this->event->first_seen->getTimestamp()),
            t('Last Seen')            => new TimeAgo($this->event->last_seen->getTimestamp()),
            t('Created')              => new TimeAgo($this->event->created->getTimestamp()),
            t('Count')                => $this->event->count,
            t('Type')                 => $this->event->type,
            t('Reason')               => $this->event->reason,
            t('Action')               => $this->event->action,
            t('Reporting Controller') => $this->event->reporting_controller,
            t('Reporting Instance')   => $this->event->reporting_instance,
            t('Reference Kind')       => $this->event->reference_kind,
            t('Reference Namespace')  => $this->event->reference_namespace,
            t('Reference Name')       => $this->event->reference_name
        ]));

        $this->addHtml(new HtmlElement(
            'section',
            new Attributes(['class' => 'event-note']),
            new HtmlElement('h2', null, new Text(t('Note'))),
            new Text($this->event->note)
        ));
    }
}

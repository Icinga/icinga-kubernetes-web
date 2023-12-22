<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class EventDetail extends BaseHtmlElement
{
    /** @var Event */
    protected $event;

    protected $tag = 'div';

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->event, [
                t('First Seen')           => new TimeAgo($this->event->first_seen->getTimestamp()),
                t('Last Seen')            => new TimeAgo($this->event->last_seen->getTimestamp()),
                t('Count')                => $this->event->count,
                t('Type')                 => $this->event->type,
                t('Reason')               => $this->event->reason,
                t('Action')               => $this->event->action,
                t('Reporting Controller') => $this->event->reporting_controller,
                t('Reporting Instance')   => $this->event->reporting_instance,
                t('Reference Kind')       => $this->event->reference_kind,
                t('Reference Namespace')  => $this->event->reference_namespace,
                t('Reference Name')       => $this->event->reference_name
            ])),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text(t('Note'))),
                new Text($this->event->note)
            )
        );
    }
}

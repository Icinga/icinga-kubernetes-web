<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\TimeAgo;

class EventDetail extends BaseHtmlElement
{
    use Translation;

    protected Event $event;

    protected $tag = 'div';

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->event, [
                $this->translate('First Seen')           => new TimeAgo($this->event->first_seen->getTimestamp()),
                $this->translate('Last Seen')            => new TimeAgo($this->event->last_seen->getTimestamp()),
                $this->translate('Count')                => $this->event->count,
                $this->translate('Type')                 => $this->event->type,
                $this->translate('Reason')               => $this->event->reason,
                $this->translate('Action')               => $this->event->action ??
                    new EmptyState($this->translate('None')),
                $this->translate('Reporting Controller') => $this->event->reporting_controller ??
                    new EmptyState($this->translate('None')),
                $this->translate('Reporting Instance')   => $this->event->reporting_instance ??
                    new EmptyState($this->translate('None')),
                $this->translate('Referent Kind')        => $this->event->reference_kind,
                $this->translate('Referent Namespace')   => $this->event->reference_namespace ??
                    new EmptyState($this->translate('None')),
                $this->translate('Referent Name')        => $this->event->reference_name
            ])),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Note'))),
                new Text($this->event->note)
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Referent'))),
                Factory::createList($this->event->reference_kind, Filter::equal('uuid', $this->event->referent_uuid))
            ),
            new Yaml($this->event->yaml)
        );
    }
}

<?php

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\EventDetail;
use ipl\Stdlib\Filter;

class EventController extends Controller
{
    public function indexAction(): void
    {
        $namespace = $this->params->get('namespace');
        $name = $this->params->get('name');
        $id = $this->params->getRequired('id');

        $this->addTitleTab("Event $namespace/$name");

        $event = Event::on(Database::connection())
            ->filter(Filter::equal('id', $id))
            ->first();

        $this->addContent(new EventDetail($event));
    }
}

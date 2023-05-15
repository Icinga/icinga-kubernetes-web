<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\DaemonSet;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class DaemonSetDetail  extends BaseHtmlElement
{
    /** @var DaemonSet */
    private $daemonSet;

    protected $tag = 'ul';

    public function __construct($daemonSet)
    {
        $this->daemonSet = $daemonSet;
    }

    protected function assemble()
    {
        $this->add(new HtmlElement('li', new Attributes(['class' => 'daemon-set-detail-item']),
            new Text(sprintf('UID: %s', $this->daemonSet->uid))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'daemon-set-detail-item']),
            new Text(sprintf('Namespace/Name: %s/%s', $this->daemonSet->namespace, $this->daemonSet->name))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'daemon-set-detail-item']),
            new Text(sprintf('Misscheduled: %s', $this->daemonSet->number_misscheduled))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'daemon-set-detail-item']),
            new Text(sprintf('Scheduled/Desired: %s/%s', $this->daemonSet->current_number_scheduled, $this->daemonSet->desired_number_scheduled))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'daemon-set-detail-item']),
            new Text(sprintf('Ready: %d', $this->daemonSet->number_ready))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'daemon-set-detail-item']),
            new Text(sprintf('Collisions: %s', $this->daemonSet->collision_count))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'daemon-set-detail-item']),
            new Text('Created: '), new TimeAgo($this->daemonSet->created->getTimestamp())));
    }

}

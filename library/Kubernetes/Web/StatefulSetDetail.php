<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\StatefulSet;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class StatefulSetDetail extends BaseHtmlElement
{
    /** @var StatefulSet */
    private $statefulSet;

    protected $tag = 'ul';

    public function __construct($statefulSet)
    {
        $this->statefulSet = $statefulSet;
    }

    protected function assemble()
    {
        $this->add(new HtmlElement('li', new Attributes(['class' => 'stateful-set-detail-item']),
            new Text(sprintf('UID: %s', $this->statefulSet->uid))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'stateful-set-detail-item']),
            new Text(sprintf('Namespace/Name: %s/%s', $this->statefulSet->namespace, $this->statefulSet->name))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'stateful-set-detail-item']),
            new Text(sprintf('Service name: %s', $this->statefulSet->service_name))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'stateful-set-detail-item']),
            new Text(sprintf('Current Revision: %s', $this->statefulSet->current_revision))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'stateful-set-detail-item']),
            new Text(sprintf('Replicas: %d/%d', $this->statefulSet->ready_replicas, $this->statefulSet->replicas))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'stateful-set-detail-item']),
            new Text(sprintf('Collisions: %s', $this->statefulSet->collision_count))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'stateful-set-detail-item']),
            new Text('Created: '), new TimeAgo($this->statefulSet->created->getTimestamp())));
    }

}
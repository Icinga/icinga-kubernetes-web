<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\ReplicaSet;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class ReplicaSetDetail extends BaseHtmlElement
{
    /** @var ReplicaSet */
    private $replicaSet;

    protected $tag = 'ul';

    public function __construct($replicaSet)
    {
        $this->replicaSet = $replicaSet;
    }

    protected function assemble()
    {
        $this->add(new HtmlElement('li', new Attributes(['class' => 'replica-set-detail-item']),
            new Text(sprintf('UID: %s', $this->replicaSet->uid))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'replica-set-detail-item']),
            new Text(sprintf('Namespace/Name: %s/%s', $this->replicaSet->namespace, $this->replicaSet->name))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'replica-set-detail-item']),
            new Text(sprintf('Desired replicas: %s', $this->replicaSet->desired_replicas))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'replica-set-detail-item']),
            new Text(sprintf('Replicas: %d/%d', $this->replicaSet->ready_replicas, $this->replicaSet->replicas))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'replica-set-detail-item']),
            new Text(sprintf('Fully labeled replicas: %s', $this->replicaSet->fully_labeled_replicas))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'replica-set-detail-item']),
            new Text('Created: '), new TimeAgo($this->replicaSet->created->getTimestamp())));
    }

}

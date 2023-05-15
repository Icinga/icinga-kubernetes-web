<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\Deployment;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class DeploymentDetail extends BaseHtmlElement
{
    /** @var Deployment */
    private $item;

    protected $tag = 'ul';

    public function __construct($item)
    {
        $this->item = $item;
    }

    protected function assemble()
    {
        $this->add(new HtmlElement('li', new Attributes(['class' => 'deployment-detail-item']),
            new Text(sprintf('UID: %s', $this->item->uid))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'deployment-detail-item']),
            new Text(sprintf('Namespace/Name: %s/%s', $this->item->namespace, $this->item->name))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'deployment-detail-item']),
            new Text(sprintf('Replicas: %d/%d', $this->item->ready_replicas, $this->item->replicas))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'deployment-detail-item']),
            new Text(sprintf('Unavailable Replicas: %d', $this->item->unavailable_replicas))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'deployment-detail-item']),
            new Text(sprintf('Strategy: %s', $this->item->strategy))));

        $this->add(new HtmlElement('li', new Attributes(['class' => 'deployment-detail-item']),
            new Text('Created: '), new TimeAgo($this->item->created->getTimestamp())));
    }

}
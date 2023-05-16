<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\Deployment;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;

class DeploymentDetail extends BaseHtmlElement
{
    /** @var Deployment */
    private $deployment;

    protected $tag = 'ul';

    public function __construct($deployment)
    {
        $this->deployment = $deployment;
    }

    protected function assemble()
    {
        $details = (new HtmlElement('div'))
            ->addHtml(new HtmlElement('h2', null, new Text('Details')))
            ->addHtml(new HorizontalKeyValue(t('Name'), $this->deployment->name))
            ->addHtml(new HorizontalKeyValue(t('Namespace'), $this->deployment->namespace))
            ->addHtml(new HorizontalKeyValue(t('Strategy'), ucfirst(Str::camel($this->deployment->strategy))))
            ->addHtml(new HorizontalKeyValue(t('Desired replicas'), $this->deployment->desired_replicas))
            ->addHtml(new HorizontalKeyValue(t('Actual replicas'), $this->deployment->actual_replicas))
            ->addHtml(new HorizontalKeyValue(t('Ready replicas'), $this->deployment->ready_replicas))
            ->addHtml(new HorizontalKeyValue(t('Unavailable replicas'), $this->deployment->unavailable_replicas));

        $this->add($details);

        $conditions = (new HtmlElement('div'));
        $conditions->add(new HtmlElement('h2', null, new Text(t('Condition history'))));

        $this->add($conditions);
    }

}
<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\DeploymentCondition;
use Icinga\Module\Kubernetes\Model\Label;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\TimeAgo;

class DeploymentDetail extends BaseHtmlElement
{
    protected $defaultAttributes = [
        'class' => 'deployment-detail',
    ];

    protected $tag = 'div';

    /** @var Deployment */
    private $deployment;

    public function __construct(Deployment $deployment)
    {
        $this->deployment = $deployment;
    }

    protected function assemble()
    {
        $this->addHtml(new Details([
            t('Name')                => $this->deployment->name,
            t('Namespace')           => $this->deployment->namespace,
            t('UID')                 => $this->deployment->uid,
            t('Strategy')            => ucfirst(Str::camel($this->deployment->strategy)),
            t('Min Ready Seconds')   => $this->deployment->min_ready_seconds,
            t('Desired Replicas')    => $this->deployment->desired_replicas,
            t('Actual Replicas')     => $this->deployment->actual_replicas,
            t('Updated Replicas')    => $this->deployment->updated_replicas,
            t('Ready Replicas')      => $this->deployment->ready_replicas,
            t('Available Replicas')  => $this->deployment->available_replicas,
            t('Unavailable Replicas') => $this->deployment->unavailable_replicas,
            t('Created')             => new TimeAgo($this->deployment->created->getTimestamp())
        ]));

        $this->addHtml(
            new Labels($this->deployment->label),
            new ConditionTable($this->deployment, (new DeploymentCondition())->getColumnDefinitions())
        );

        $this->addHtml(new HtmlElement(
            'section',
            new Attributes(['class' => 'resource-replica-sets']),
            new HtmlElement('h2', null, new Text(t('Replica Sets'))),
            new ReplicaSetList($this->deployment->replica_sets)
        ));
    }
}

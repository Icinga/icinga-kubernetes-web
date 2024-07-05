<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\DeploymentCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;
use ipl\Web\Widget\StateBall;

class DeploymentDetail extends BaseHtmlElement
{
    use Translation;

    protected $defaultAttributes = [
        'class' => 'deployment-detail'
    ];

    /** @var Deployment */
    protected $deployment;

    protected $tag = 'div';

    public function __construct(Deployment $deployment)
    {
        $this->deployment = $deployment;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->deployment, [
                $this->translate('Strategy')             => ucfirst(Str::camel($this->deployment->strategy)),
                $this->translate('Min Ready Seconds')    => $this->deployment->min_ready_seconds,
                $this->translate('Desired Replicas')     => $this->deployment->desired_replicas,
                $this->translate('Actual Replicas')      => $this->deployment->actual_replicas,
                $this->translate('Updated Replicas')     => $this->deployment->updated_replicas,
                $this->translate('Ready Replicas')       => $this->deployment->ready_replicas,
                $this->translate('Available Replicas')   => $this->deployment->available_replicas,
                $this->translate('Unavailable Replicas') => $this->deployment->unavailable_replicas,
                $this->translate('Icinga State')         => (new HtmlDocument())
                    ->addHtml(new StateBall($this->deployment->icinga_state, StateBall::SIZE_MEDIUM))
                    ->addHtml(new HtmlElement('span', null, Text::create(' ' . $this->deployment->icinga_state))),
                $this->translate('Icinga State Reason')  => new HtmlElement(
                    'div',
                    new Attributes(['class' => 'state-reason detail']),
                    Text::create($this->deployment->icinga_state_reason)
                )
            ])),
            new Labels($this->deployment->label),
            new Annotations($this->deployment->annotation),
			new DeploymentConditions($this->deployment),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Replica Sets'))),
                new ReplicaSetList($this->deployment->replica_set)
            ),
            new Yaml($this->deployment->yaml)
        );
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\DeploymentCondition;
use Icinga\Module\Kubernetes\Model\Event;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class DeploymentDetail extends BaseHtmlElement
{
    use Translation;

    protected Deployment $deployment;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'deployment-detail'];

    public function __construct(Deployment $deployment)
    {
        $this->deployment = $deployment;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->deployment, [
                $this->translate('Min Ready Duration')   => (new HtmlDocument())->addHtml(
                    new Icon('stopwatch'),
                    new Text(Format::seconds($this->deployment->min_ready_seconds, $this->translate('None')))
                ),
                $this->translate('Strategy')             => (new HtmlDocument())->addHtml(
                    new Icon('retweet'),
                    new Text($this->deployment->strategy)
                ),
                $this->translate('Progress Deadline')    => (new HtmlDocument())->addHtml(
                    new Icon('skull-crossbones'),
                    new Text(Format::seconds($this->deployment->progress_deadline_seconds))
                ),
                $this->translate('Desired Replicas')     => $this->deployment->desired_replicas,
                $this->translate('Actual Replicas')      => $this->deployment->actual_replicas,
                $this->translate('Updated Replicas')     => $this->deployment->updated_replicas,
                $this->translate('Ready Replicas')       => $this->deployment->ready_replicas,
                $this->translate('Available Replicas')   => $this->deployment->available_replicas,
                $this->translate('Unavailable Replicas') => $this->deployment->unavailable_replicas,
                $this->translate('Icinga State')         => (new HtmlDocument())->addHtml(
                    new StateBall($this->deployment->icinga_state, StateBall::SIZE_MEDIUM),
                    new HtmlElement(
                        'span',
                        new Attributes(['class' => 'icinga-state-text']),
                        new Text($this->deployment->icinga_state)
                    )
                ),
                $this->translate('Icinga State Reason')  => new IcingaStateReason(
                    $this->deployment->icinga_state_reason
                )
            ])),
            new Labels($this->deployment->label),
            new Annotations($this->deployment->annotation),
            new ConditionTable($this->deployment, (new DeploymentCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Replica Sets'))),
                new ReplicaSetList($this->deployment->replica_set)
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(
                    Event::on(Database::connection())
                        ->filter(Filter::equal('referent_uuid', $this->deployment->uuid))
                )
            ),
            new Yaml($this->deployment->yaml)
        );
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\replicaSetCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class ReplicaSetDetail extends BaseHtmlElement
{
    use Translation;

    protected ReplicaSet $replicaSet;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'replica-set-detail'];

    public function __construct(ReplicaSet $replicaSet)
    {
        $this->replicaSet = $replicaSet;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->replicaSet, [
                $this->translate('Min Ready Duration')     => (new HtmlDocument())->addHtml(
                    new Icon('stopwatch'),
                    new Text(Format::seconds($this->replicaSet->min_ready_seconds, $this->translate('None')))
                ),
                $this->translate('Desired Replicas')       => $this->replicaSet->desired_replicas,
                $this->translate('Actual Replicas')        => $this->replicaSet->actual_replicas,
                $this->translate('Fully Labeled Replicas') => $this->replicaSet->fully_labeled_replicas,
                $this->translate('Ready Replicas')         => $this->replicaSet->ready_replicas,
                $this->translate('Available Replicas')     => $this->replicaSet->available_replicas,
                $this->translate('Icinga State')           => (new HtmlDocument())->addHtml(
                    new StateBall($this->replicaSet->icinga_state, StateBall::SIZE_MEDIUM),
                    new HtmlElement(
                        'span',
                        new Attributes(['class' => 'icinga-state-text']),
                        Text::create($this->replicaSet->icinga_state)
                    )
                ),
                $this->translate('Icinga State Reason')    => new IcingaStateReason(
                    $this->replicaSet->icinga_state_reason
                )
            ])),
            new Labels($this->replicaSet->label),
            new Annotations($this->replicaSet->annotation),
            new ReplicaSetConditions($this->replicaSet),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                new PodList($this->replicaSet->pod->with(['node']))
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(
                    Event::on(Database::connection())
                        ->filter(Filter::equal('referent_uuid', $this->replicaSet->uuid))
                )
            ),
            new Yaml($this->replicaSet->yaml)
        );
    }
}

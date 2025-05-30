<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Detail;

use DateInterval;
use DateTime;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Common\Metrics;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\Widget\Annotations;
use Icinga\Module\Kubernetes\Web\Widget\Chart\DetailMetricCharts;
use Icinga\Module\Kubernetes\Web\Widget\Conditions\ReplicaSetConditions;
use Icinga\Module\Kubernetes\Web\Widget\Details;
use Icinga\Module\Kubernetes\Web\Widget\DetailState;
use Icinga\Module\Kubernetes\Web\Widget\Environment\ReplicaSetEnvironment;
use Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason\WorkloadIcingaStateReason;
use Icinga\Module\Kubernetes\Web\Widget\Labels;
use Icinga\Module\Kubernetes\Web\Widget\Yaml;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;

class ReplicaSetDetail extends BaseHtmlElement
{
    use Translation;

    protected ReplicaSet $replicaSet;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'object-detail replica-set-detail'];

    public function __construct(ReplicaSet $replicaSet)
    {
        $this->replicaSet = $replicaSet;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text($this->translate('Icinga State Reason'))),
            new WorkloadIcingaStateReason(
                $this->replicaSet->uuid,
                $this->replicaSet->icinga_state_reason,
                $this->replicaSet->icinga_state
            )
        ));

        $this->addHtml(
            new DetailMetricCharts(
                Metrics::replicaSetMetrics(
                    (new DateTime())->sub(new DateInterval('PT12H')),
                    $this->replicaSet->uuid,
                    Metrics::POD_CPU_USAGE,
                    Metrics::POD_MEMORY_USAGE,
                )
            ),
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
                $this->translate('Icinga State')           => new DetailState($this->replicaSet->icinga_state)
            ])),
            new Labels($this->replicaSet->label),
            new Annotations($this->replicaSet->annotation),
            new ReplicaSetConditions($this->replicaSet),
            new ReplicaSetEnvironment($this->replicaSet),
        );

        if (Auth::getInstance()->hasPermission(Auth::SHOW_PODS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                (new ResourceList(Auth::getInstance()->withRestrictions(
                    Auth::SHOW_PODS,
                    $this->replicaSet->pod->with(['node'])
                )))
                    ->setViewMode(ViewMode::Common)
                    ->setCollapsible()
            ));
        }

        if (Auth::getInstance()->hasPermission(Auth::SHOW_EVENTS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                (new ResourceList(Event::on(Database::connection())
                    ->filter(Filter::equal('reference_uuid', $this->replicaSet->uuid))))
                    ->setViewMode(ViewMode::Common)
                    ->setCollapsible()
            ));
        }

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->replicaSet->yaml));
        }
    }
}

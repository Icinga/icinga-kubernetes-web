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
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Model\StatefulSetCondition;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\Widget\Annotations;
use Icinga\Module\Kubernetes\Web\Widget\Chart\DetailMetricCharts;
use Icinga\Module\Kubernetes\Web\Widget\ConditionTable;
use Icinga\Module\Kubernetes\Web\Widget\Details;
use Icinga\Module\Kubernetes\Web\Widget\DetailState;
use Icinga\Module\Kubernetes\Web\Widget\Environment\StatefulSetEnvironment;
use Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason\WorkloadIcingaStateReason;
use Icinga\Module\Kubernetes\Web\Widget\KIcon;
use Icinga\Module\Kubernetes\Web\Widget\Labels;
use Icinga\Module\Kubernetes\Web\Widget\Yaml;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;

class StatefulSetDetail extends BaseHtmlElement
{
    use Translation;

    protected StatefulSet $statefulSet;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'object-detail stateful-set-detail'];

    public function __construct(StatefulSet $statefulSet)
    {
        $this->statefulSet = $statefulSet;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text($this->translate('Icinga State Reason'))),
            new WorkloadIcingaStateReason(
                $this->statefulSet->uuid,
                $this->statefulSet->icinga_state_reason,
                $this->statefulSet->icinga_state
            )
        ));

        $this->addHtml(
            new DetailMetricCharts(
                Metrics::statefulSetMetrics(
                    (new DateTime())->sub(new DateInterval('PT12H')),
                    $this->statefulSet->uuid,
                    Metrics::POD_CPU_USAGE,
                    Metrics::POD_MEMORY_USAGE,
                )
            ),
            new Details(new ResourceDetails($this->statefulSet, [
                $this->translate('Min Ready Duration')    => (new HtmlDocument())->addHtml(
                    new Icon('stopwatch'),
                    new Text(Format::seconds($this->statefulSet->min_ready_seconds, $this->translate('None')))
                ),
                $this->translate('Update Strategy')       => (new HtmlDocument())->addHtml(
                    new Icon('retweet'),
                    new Text($this->statefulSet->update_strategy)
                ),
                $this->translate('Pod Management Policy') => (new HtmlDocument())->addHtml(
                    new Icon('shuffle'),
                    new Text($this->statefulSet->pod_management_policy)
                ),
                $this->translate('Service Name')          => (new HtmlDocument())->addHtml(
                    new KIcon('service'),
                    new Text($this->statefulSet->service_name)
                ),
                $this->translate('Desired Replicas')      => $this->statefulSet->desired_replicas,
                $this->translate('Actual Replicas')       => $this->statefulSet->actual_replicas,
                $this->translate('Current Replicas')      => $this->statefulSet->current_replicas,
                $this->translate('Updated Replicas')      => $this->statefulSet->updated_replicas,
                $this->translate('Ready Replicas')        => $this->statefulSet->ready_replicas,
                $this->translate('Available Replicas')    => $this->statefulSet->available_replicas,
                $this->translate('Icinga State')          => new DetailState($this->statefulSet->icinga_state)
            ])),
            new Labels($this->statefulSet->label),
            new Annotations($this->statefulSet->annotation),
            new ConditionTable($this->statefulSet, (new StatefulSetCondition())->getColumnDefinitions()),
            new StatefulSetEnvironment($this->statefulSet),
        );

        if (Auth::getInstance()->hasPermission(Auth::SHOW_PODS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                (new ResourceList(Auth::getInstance()->withRestrictions(
                    Auth::SHOW_PODS,
                    $this->statefulSet->pod->with(['node'])
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
                    ->filter(Filter::equal('reference_uuid', $this->statefulSet->uuid))))
                    ->setViewMode(ViewMode::Common)
                    ->setCollapsible()
            ));
        }

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->statefulSet->yaml));
        }
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Metrics;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;
use DateTime;
use DateInterval;

class PodDetail extends BaseHtmlElement
{
    use Translation;

    protected Pod $pod;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'pod-detail'];

    public function __construct(Pod $pod)
    {
        $this->pod = $pod;
    }

    protected function assemble(): void
    {
        $containerRestarts = 0;
        /** @var Container $container */
        foreach ($this->pod->container as $container) {
            $containerRestarts += $container->restart_count;
        }

        $metrics = new Metrics(Database::connection());
        $podMetricsPeriod = $metrics->getPodMetrics(
            (new DateTime())->sub(new DateInterval('PT12H')),
            $this->pod->uuid,
            Metrics::POD_CPU_USAGE,
            Metrics::POD_MEMORY_USAGE
        );
        $metricRow = [];
        if (isset($podMetricsPeriod[Metrics::POD_CPU_USAGE])) {
            $metricRow[] = new LineChart(
                'chart-medium',
                implode(', ', $podMetricsPeriod[Metrics::POD_CPU_USAGE]),
                implode(', ', array_keys($podMetricsPeriod[Metrics::POD_CPU_USAGE])),
                'CPU Usage',
                Metrics::COLOR_CPU
            );
        }
        if (isset($podMetricsPeriod[Metrics::POD_MEMORY_USAGE])) {
            $metricRow[] = new LineChart(
                'chart-medium',
                implode(', ', $podMetricsPeriod[Metrics::POD_MEMORY_USAGE]),
                implode(', ', array_keys($podMetricsPeriod[Metrics::POD_MEMORY_USAGE])),
                'Memory Usage',
                Metrics::COLOR_MEMORY
            );
        }

        $this->addHtml(
            new MetricCharts($metricRow),
            new Details(new ResourceDetails($this->pod, [
                $this->translate('IP')                  => $this->pod->ip,
                $this->translate('Node')                => (new HtmlDocument())->addHtml(
                    new Icon('share-nodes'),
                    new Text($this->pod->node_name)
                ),
                $this->translate('QoS Class')           => (new HtmlDocument())->addHtml(
                    (new Icon(PodListItem::QOS_ICONS[$this->pod->qos]))
                        ->addAttributes([
                            'title' => sprintf(
                                '%s: %s',
                                $this->translate('Quality of Service'),
                                $this->pod->qos
                            )
                        ]),
                    new Text($this->pod->qos)
                ),
                $this->translate('Restart Policy')      => $this->pod->restart_policy,
                $this->translate('Container Restarts')  => $containerRestarts,
                $this->translate('Phase')               => $this->pod->phase,
                $this->translate('Reason')              => $this->pod->reason,
                $this->translate('Message')             => $this->pod->message,
                $this->translate('Icinga State')        => (new HtmlDocument())->addHtml(
                    new StateBall($this->pod->icinga_state, StateBall::SIZE_MEDIUM),
                    new HtmlElement(
                        'span',
                        new Attributes(['class' => 'icinga-state-text']),
                        new Text($this->pod->icinga_state)
                    )
                ),
                $this->translate('Icinga State Reason') => new IcingaStateReason($this->pod->icinga_state_reason)
            ])),
            new Labels($this->pod->label),
            new Annotations($this->pod->annotation),
            new ConditionTable($this->pod, (new PodCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Containers')),
                new ContainerList($this->pod->container)
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Events')),
                new EventList(
                    Event::on(Database::connection())
                        ->filter(
                            Filter::all(
                                Filter::equal('reference_kind', 'Pod'),
                                Filter::equal('reference_namespace', $this->pod->namespace),
                                Filter::equal('reference_name', $this->pod->name)
                            )
                        )
                )
            ),
            new Yaml($this->pod->yaml)
        );
    }
}

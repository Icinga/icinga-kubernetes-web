<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Metrics;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodCondition;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;
use ipl\Web\Widget\CopyToClipboard;
use ipl\Web\Widget\StateBall;
use DateTime;
use DateInterval;

class PodDetail extends BaseHtmlElement
{
    use Translation;

    /** @var Pod */
    protected $pod;

    protected $tag = 'div';

    public function __construct(Pod $pod)
    {
        $this->pod = $pod;
    }

    protected function assemble()
    {
        $icingaStateReason = new PluginOutputContainer(new PluginOutput($this->pod->icinga_state_reason));
        CopyToClipboard::attachTo($icingaStateReason);

        $metrics = new Metrics(Database::connection());
        $podMetricsPeriod = $metrics->getPodMetrics(
            (new DateTime())->sub(new DateInterval('PT12H')),
            $this->pod->id,
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
                $this->translate('Node')                => $this->pod->node_name,
                $this->translate('QoS Class')           => ucfirst(Str::camel($this->pod->qos)),
                $this->translate('Restart Policy')      => ucfirst(Str::camel($this->pod->restart_policy)),
                $this->translate('Phase')               => $this->pod->phase,
                $this->translate('Icinga State')        => (new HtmlDocument())
                    ->addHtml(new StateBall($this->pod->icinga_state, StateBall::SIZE_MEDIUM))
                    ->addHtml(new HtmlElement('span', null, Text::create(' ' . $this->pod->icinga_state))),
                $this->translate('Icinga State Reason') => $icingaStateReason
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

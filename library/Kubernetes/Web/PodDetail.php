<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use DateInterval;
use DateTime;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Metrics;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Pod;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\Icon;

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

        $this->addHtml(
            new DetailMetricCharts(
                Metrics::podMetrics(
                    (new DateTime())->sub(new DateInterval('PT12H')),
                    $this->pod->uuid,
                    Metrics::POD_CPU_USAGE,
                    Metrics::POD_MEMORY_USAGE
                )
            ),
            new Details(new ResourceDetails($this->pod, [
                $this->translate('IP')                  => $this->pod->ip ??
                    new EmptyState($this->translate('None')),
                $this->translate('Node')                => (new HtmlDocument())->addHtml(
                    new Icon('share-nodes'),
                    $this->pod->node_name ?
                        new Text($this->pod->node_name) :
                        new EmptyState($this->translate('None')),
                ),
                $this->translate('Container Restarts')  => (new HtmlDocument())->addHtml(
                    new Icon('arrows-spin'),
                    new Text($containerRestarts)
                ),
                $this->translate('Restart Policy')      => (new HtmlDocument())->addHtml(
                    new Icon('recycle'),
                    new Text($this->pod->restart_policy)
                ),
                $this->translate('Quality of Service')  => (new HtmlDocument())->addHtml(
                    new Icon('life-ring'),
                    new Text($this->pod->qos)
                ),
                $this->translate('Phase')               => $this->pod->phase,
                $this->translate('Reason')              => $this->pod->reason ??
                    new EmptyState($this->translate('None')),
                $this->translate('Message')             => $this->pod->message ??
                    new EmptyState($this->translate('None')),
                $this->translate('Icinga State')        => new DetailState($this->pod->icinga_state),
                $this->translate('Icinga State Reason') => new IcingaStateReason($this->pod->icinga_state_reason)
            ])),
            new Labels($this->pod->label),
            new Annotations($this->pod->annotation),
            new PodConditions($this->pod),
            new PodEnvironment($this->pod),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Init Containers')),
                new InitContainerList($this->pod->init_container)
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Sidecar Containers')),
                new SidecarContainerList($this->pod->sidecar_container)
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Containers')),
                new ContainerList($this->pod->container)
            )
        );

        if (Auth::getInstance()->hasPermission(Auth::SHOW_PERSISTENT_VOLUME_CLAIMS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Persistent Volume Claims'))),
                (new PersistentVolumeClaimList(Auth::getInstance()->withRestrictions(
                    Auth::SHOW_PERSISTENT_VOLUME_CLAIMS,
                    $this->pod->pvc
                )))->setViewMode(ViewModeSwitcher::VIEW_MODE_DETAILED)
            ));
        }

        if (Auth::getInstance()->hasPermission(Auth::SHOW_EVENTS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Events')),
                (new EventList(Event::on(Database::connection())
                    ->filter(Filter::equal('reference_uuid', $this->pod->uuid))))
                    ->setViewMode(ViewModeSwitcher::VIEW_MODE_COMMON)
            ));
        }

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->pod->yaml));
        }
    }
}

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
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\Widget\Annotations;
use Icinga\Module\Kubernetes\Web\Widget\Chart\DetailMetricCharts;
use Icinga\Module\Kubernetes\Web\Widget\Conditions\DeploymentConditions;
use Icinga\Module\Kubernetes\Web\Widget\Details;
use Icinga\Module\Kubernetes\Web\Widget\DetailState;
use Icinga\Module\Kubernetes\Web\Widget\Environment\DeploymentEnvironment;
use Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason\DeploymentIcingaStateReason;
use Icinga\Module\Kubernetes\Web\Widget\Labels;
use Icinga\Module\Kubernetes\Web\Widget\Yaml;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;

class DeploymentDetail extends BaseHtmlElement
{
    use Translation;

    protected Deployment $deployment;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'object-detail deployment-detail'];

    public function __construct(Deployment $deployment)
    {
        $this->deployment = $deployment;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text($this->translate('Icinga State Reason'))),
            new DeploymentIcingaStateReason(
                $this->deployment->uuid,
                $this->deployment->icinga_state_reason,
                $this->deployment->icinga_state
            )
        ));

        $this->addHtml(
            new DetailMetricCharts(
                Metrics::deploymentMetrics(
                    (new DateTime())->sub(new DateInterval('PT12H')),
                    $this->deployment->uuid,
                    Metrics::POD_CPU_USAGE,
                    Metrics::POD_MEMORY_USAGE,
                )
            ),
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
                $this->translate('Icinga State')         => new DetailState($this->deployment->icinga_state)
            ])),
            new Labels($this->deployment->label),
            new Annotations($this->deployment->annotation),
            new DeploymentConditions($this->deployment),
            new DeploymentEnvironment($this->deployment),
        );

        if (Auth::getInstance()->hasPermission(Auth::SHOW_REPLICA_SETS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Replica Sets'))),
                (new ResourceList(Auth::getInstance()->withRestrictions(
                    Auth::SHOW_REPLICA_SETS,
                    $this->deployment->replica_set
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
                    ->filter(Filter::equal('reference_uuid', $this->deployment->uuid))))
                    ->setViewMode(ViewMode::Common)
                    ->setCollapsible()
            ));
        }

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->deployment->yaml));
        }
    }
}

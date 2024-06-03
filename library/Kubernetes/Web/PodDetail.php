<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Annotation;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodCondition;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;

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
        $this->addHtml(
            new Details(new ResourceDetails($this->pod, [
                $this->translate('IP')             => $this->pod->ip,
                $this->translate('Node')           => $this->pod->node_name,
                $this->translate('QoS Class')      => ucfirst(Str::camel($this->pod->qos)),
                $this->translate('Restart Policy') => ucfirst(Str::camel($this->pod->restart_policy)),
                $this->translate('Phase')          => $this->pod->phase
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
            )
        );
    }
}

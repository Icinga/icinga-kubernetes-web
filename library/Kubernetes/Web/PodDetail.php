<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Icingadb\Model\Behavior\ActionAndNoteUrl;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodCondition;
use Icinga\Web\Form\Decorator\ElementDoubler;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;
use ipl\Web\Widget\CopyToClipboard;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\TimeAgo;

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

        $this->addHtml(
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

            /* new Labels($this->pod->label), */

            new HtmlElement('section', null,
                new HtmlElement('h2', null, new Text('Environment')),
                new HtmlElement('div', new Attributes(['class' => 'environment-widget']),
                    new HtmlElement('span', new Attributes(['class' => 'label-namespace']),
                        new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-ns'])),
                        new Text(' '),
                        new Text('namespace')
                    ),
                    new HtmlElement('ul', null,
                        new HtmlElement('li', new Attributes(['class' => 'breadcrumb breadcrumb-right']),
                            new StateBall('ok', StateBall::SIZE_MEDIUM),
                            new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-ingress'])),
                            new Text(' '),
                            new HtmlElement('span', new Attributes(['class' => 'resource-name']),
                                new Text('ing-name-random')
                            )
                        ),
                        new HtmlElement('li', new Attributes(['class' => 'breadcrumb breadcrumb-right']),
                            new HtmlElement('span', new Attributes(['class' => 'resource-name']),
                                new StateBall('ok', StateBall::SIZE_MEDIUM),
                                new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-svc'])),
                                new Text(' '),
                                new Text('service-name-random')
                            )
                        ),
                        new HtmlElement('li', new Attributes(['class' => 'breadcrumb']),
                            new HtmlElement('span', new Attributes(['class' => 'resource-name']),
                                new StateBall('ok', StateBall::SIZE_MEDIUM),
                                new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-ingress'])),
                                new Text(' '),
                                new Text('ing-name-random')
                            )
                        ),
                        new HtmlElement('li', new Attributes(['class' => 'breadcrumb breadcrumb-left']),
                            new HtmlElement('span', new Attributes(['class' => 'resource-name']),
                                new StateBall('ok', StateBall::SIZE_MEDIUM),
                                new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-ingress'])),
                                new Text(' '),
                                new Text('rs-name-random')
                            )
                        )
                    )
                )
            ),

            /* new Labels($this->pod->label), */
            new HtmlElement('section', null,
                new HtmlElement('h2', null, new Text($this->translate('Labels'))),
                new HtmlElement('ul', new Attributes(['class' => 'labels']),
                    new HtmlElement('li', null,
                        new HtmlElement('span', new Attributes(['class' => 'title']),
                            new Text('app.kubernetes.io')
                        ),
                        new HtmlElement('ul', null,
                            new HtmlElement('li', null, new HorizontalKeyValue('instance', 'redis-cluster')),
                            new HtmlElement('li', null, new HorizontalKeyValue('name', 'redis-cluster')),
                            new HtmlElement('li', null, new HorizontalKeyValue('managed-by', 'Helm'))
                        )
                    ),
                    new HtmlElement('li', null,
                        new HtmlElement('span', new Attributes(['class' => 'title']),
                            new Text('helm.sh')
                        ),
                        new HtmlElement('ul', null,
                            new HtmlElement('li', null, new HorizontalKeyValue('chart', 'redis-cluster-862')),
                            new HtmlElement('li', null, new HorizontalKeyValue('something', 'true'))
                        )
                    )
                )
            ),

            /* new ConditionTable($this->pod, (new PodCondition())->getColumnDefinitions()),*/
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Conditions')),
                new HtmlElement('ul', new Attributes(['class' => 'condition-list item-list']),
                    new HtmlElement('li', new Attributes(['class' => 'list-item']),
                        new HtmlElement('div', new Attributes(['class' => 'visual inactive']),
                            (new Icon('circle'))->setStyle('fa-regular')
                        ),
                        new HtmlElement('div', new Attributes(['class' => 'main']),
                            new HtmlElement('header', null,
                                new HtmlElement('h3', null, new Text('Ready'))
                            )
                        )
                    ),
                    new HtmlElement('li', new Attributes(['class' => 'list-item']),
                        new HtmlElement('div', new Attributes(['class' => 'visual error']),
                            new Icon('times-circle')
                        ),
                        new HtmlElement('div', new Attributes(['class' => 'main']),
                            new HtmlElement('header', null,
                                new HtmlElement('h3', null, new Text('Containers Ready')),
                                new TimeAgo(1715752000)
                            ),
                            new HtmlElement('section', new Attributes(['class' => 'caption']),
                                new PluginOutputContainer(new PluginOutput('Nullam id dolor id nibh ultricies vehicula ut id elit. Maecenas faucibus mollis interdum. Maecenas faucibus mollis interdum. Etiam porta sem malesuada magna mollis euismod. Sed posuere consectetur est at lobortis.'))
                            )
                        )
                    ),
                    new HtmlElement('li', new Attributes(['class' => 'list-item']),
                        new HtmlElement('div', new Attributes(['class' => 'visual success']),
                            (new Icon('check-circle'))->setStyle('fa-regular')
                        ),
                        new HtmlElement('div', new Attributes(['class' => 'main']),
                            new HtmlElement('header', null,
                                new HtmlElement('h3', null, new Text('Scheduled')),
                                new TimeAgo(1715740000)
                            )
                        )
                    ),
                    new HtmlElement('li', new Attributes(['class' => 'list-item']),
                        new HtmlElement('div', new Attributes(['class' => 'visual success']),
                            (new Icon('check-circle'))->setStyle('fa-regular')
                        ),
                        new HtmlElement('div', new Attributes(['class' => 'main']),
                            new HtmlElement('header', null,
                                new HtmlElement('h3', null, new Text('Initialized')),
                                new TimeAgo(1715690000)
                            )
                        )
                    )
                )
            ),
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

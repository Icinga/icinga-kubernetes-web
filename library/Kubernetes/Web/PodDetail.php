<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

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
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

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
            new PodConditions($this->pod),
            new HtmlElement('section', null,
                new HtmlElement('h2', null, new Text('Environment')),
                new HtmlElement('div', new Attributes(['class' => 'environment-widget']),
                    new HtmlElement('span', new Attributes(['class' => 'label-namespace']),
                        new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-ns'])),
                        new Text('namespace')
                    ),
                    new HtmlElement('ul', new Attributes(['class' => 'breadcrumbs']),
                        new HtmlElement('li', new Attributes(['class' => 'breadcrumb-right']),
                            new HtmlElement('a', new Attributes([
                                'href' => '/'
                            ]),
                                new StateBall('ok', StateBall::SIZE_MEDIUM),
                                new Text(' '),
                                new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-ingress'])),
                                new Text('ing-name-random')
                            )
                        ),
                        new HtmlElement('li', new Attributes(['class' => 'breadcrumb-right']),
                            new HtmlElement('a', new Attributes([
                                'href' => '/'
                            ]),
                                new StateBall('ok', StateBall::SIZE_MEDIUM),
                                new Text(' '),
                                new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-svc'])),
                                new Text('svc-name-random')
                            )
                        ),
                        new HtmlElement('li', new Attributes(['class' => 'breadcrumb-middle active']),
                            new HtmlElement('span', null,
                                new StateBall('ok', StateBall::SIZE_MEDIUM),
                                new Text(' '),
                                new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-pod'])),
                                new Text('3'),
                                new Text('(83)')
                            )

                        ),
                        new HtmlElement('li', new Attributes(['class' => 'breadcrumb-left']),
                            new HtmlElement('a', new Attributes([
                                'href' => '/'
                            ]),
                                new StateBall('ok', StateBall::SIZE_MEDIUM),
                                new Text(' '),
                                new HtmlElement('i', new Attributes(['class' => 'ikicon-kubernetes ikicon-kubernetes-rs'])),
                                new Text('rs-name-random')
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

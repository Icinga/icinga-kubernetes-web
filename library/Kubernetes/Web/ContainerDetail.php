<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use DateTime;
use Icinga\Module\Kubernetes\Model\Container;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\TimeAgo;

class ContainerDetail extends BaseHtmlElement
{
    /** @var Container */
    protected $container;

    protected $tag = 'div';

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function assemble()
    {
        $this->addHtml(new Details([
            t('Name')          => $this->container->name,
            t('Image')         => $this->container->image,
            t('Started')       => new Icon($this->container->started ? 'check' : 'xmark'),
            t('Ready')         => new Icon($this->container->ready ? 'check' : 'xmark'),
            t('Restart Count') => $this->container->restart_count
        ]));

        $state = new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text(t('State'))),
            new HorizontalKeyValue(t('State'), ucfirst(Str::camel($this->container->state)))
        );
        $stateDetails = json_decode($this->container->state_details);
        switch ($this->container->state) {
            case Container::STATE_RUNNING:
                $state->addHtml(
                    new HorizontalKeyValue(
                        t('Started At'),
                        new TimeAgo((new DateTime($stateDetails->startedAt))->getTimestamp())
                    )
                );

                break;
            case Container::STATE_TERMINATED:
            case Container::STATE_WAITING:
                $state->addHtml(new HorizontalKeyValue('Reason', $stateDetails->reason));
                if (isset($stateDetails->message)) {
                    $state->addHtml(new HorizontalKeyValue('Message', $stateDetails->message));
                }

                break;
            default:
                $state->addHtml(new FormattedString('Unknown state %s', $this->container->state));
        }
        $this->addHtml($state);

        $this->addHtml(new HtmlElement(
            'section',
            new Attributes(['class' => 'logs']),
            new HtmlElement('h2', null, new Text(t('Logs'))),
            new HtmlElement('p', null, new Text($this->container->log->logs))
        ));
    }
}

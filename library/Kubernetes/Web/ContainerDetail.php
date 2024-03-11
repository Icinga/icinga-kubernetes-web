<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use DateTime;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Model\Container;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\TimeAgo;

class ContainerDetail extends BaseHtmlElement
{
    use Translation;

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
            $this->translate('Name')          => $this->container->name,
            $this->translate('Image')         => $this->container->image,
            $this->translate('Started')       => Icons::ready($this->container->started),
            $this->translate('Ready')         => Icons::ready($this->container->ready),
            $this->translate('Restart Count') => $this->container->restart_count
        ]));

        $state = new HtmlElement(
            'section',
            new Attributes(['class' => 'state']),
            new HtmlElement('h2', null, new Text($this->translate('State'))),
            new HorizontalKeyValue($this->translate('State'), ucfirst(Str::camel($this->container->state)))
        );
        $stateDetails = json_decode($this->container->state_details);
        switch ($this->container->state) {
            case Container::STATE_RUNNING:
                $state->addHtml(
                    new HorizontalKeyValue(
                        $this->translate('Started At'),
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
            new HtmlElement('h2', null, new Text($this->translate('Logs'))),
            new HtmlElement('p', null, new Text($this->container->log->logs))
        ));
    }
}

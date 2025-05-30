<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Detail;

use DateTime;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\ContainerLog;
use Icinga\Module\Kubernetes\Web\Widget\Details;
use Icinga\Module\Kubernetes\Web\Widget\DetailState;
use Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason\IcingaStateReason;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlDocument;
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

    protected Container $container;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'object-detail container-detail'];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text($this->translate('Icinga State Reason'))),
            new IcingaStateReason($this->container->icinga_state_reason, $this->container->icinga_state)
        ));

        $this->addHtml(new Details([
            $this->translate('Name')                => $this->container->name,
            $this->translate('Image')               => $this->container->image,
            $this->translate('Image Pull Policy')   => (new HtmlDocument())->addHtml(
                new Icon('download'),
                new Text($this->container->image_pull_policy)
            ),
            $this->translate('Started')             => Icons::ready($this->container->started),
            $this->translate('Ready')               => Icons::ready($this->container->ready),
            $this->translate('Restarts')            => (new HtmlDocument())->addHtml(
                new Icon('arrows-spin'),
                new Text($this->container->restart_count)
            ),
            $this->translate('Icinga State')        => new DetailState($this->container->icinga_state)
        ]));

        $state = new HtmlElement(
            'section',
            new Attributes(['class' => 'state']),
            new HtmlElement('h2', null, new Text($this->translate('State'))),
            new HorizontalKeyValue($this->translate('State'), ucfirst(Str::camel($this->container->state)))
        );
        $stateDetails = json_decode((string) $this->container->state_details);
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
                $state->addHtml(new FormattedString('Unknown state %s', [$this->container->state]));
        }
        $this->addHtml($state);

        if ($this->container->log instanceof ContainerLog) {
            // Does not seem to work for Sidecar and Init containers.
            $this->addHtml(new HtmlElement(
                'section',
                new Attributes(['class' => 'logs']),
                new HtmlElement('h2', null, new Text($this->translate('Logs'))),
                new HtmlElement('p', null, new Text($this->container->log->logs))
            ));
        }
    }
}

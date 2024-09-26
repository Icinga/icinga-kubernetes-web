<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use DateTime;
use Icinga\Module\Kubernetes\Model\InitContainer;
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
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class InitContainerDetail extends BaseHtmlElement
{
    use Translation;

    protected InitContainer $initContainer;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'init-container-detail'];

    public function __construct(InitContainer $initContainer)
    {
        $this->initContainer = $initContainer;
    }

    protected function assemble(): void
    {
        $this->addHtml(new Details([
            $this->translate('Name')                => $this->initContainer->name,
            $this->translate('Image')               => $this->initContainer->image,
            $this->translate('Image Pull Policy')   => (new HtmlDocument())->addHtml(
                new Icon('download'),
                new Text($this->initContainer->image_pull_policy)
            ),
            $this->translate('Icinga State')        => (new HtmlDocument())->addHtml(
                new StateBall($this->initContainer->icinga_state, StateBall::SIZE_MEDIUM),
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'icinga-state-text']),
                    new Text($this->initContainer->icinga_state)
                )
            ),
            $this->translate('Icinga State Reason') => new IcingaStateReason($this->initContainer->icinga_state_reason)
        ]));

        $state = new HtmlElement(
            'section',
            new Attributes(['class' => 'state']),
            new HtmlElement('h2', null, new Text($this->translate('State'))),
            new HorizontalKeyValue($this->translate('State'), ucfirst(Str::camel($this->initContainer->state)))
        );
        $stateDetails = json_decode((string) $this->initContainer->state_details);
        switch ($this->initContainer->state) {
            case InitContainer::STATE_RUNNING:
                $state->addHtml(
                    new HorizontalKeyValue(
                        $this->translate('Started At'),
                        new TimeAgo((new DateTime($stateDetails->startedAt))->getTimestamp())
                    )
                );

                break;
            case InitContainer::STATE_TERMINATED:
            case InitContainer::STATE_WAITING:
                $state->addHtml(new HorizontalKeyValue('Reason', $stateDetails->reason));
                if (isset($stateDetails->message)) {
                    $state->addHtml(new HorizontalKeyValue('Message', $stateDetails->message));
                }

                break;
            default:
                $state->addHtml(new FormattedString('Unknown state %s', [$this->initContainer->state]));
        }
        $this->addHtml($state);
    }
}

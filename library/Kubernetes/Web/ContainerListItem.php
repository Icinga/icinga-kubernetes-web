<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use DateTime;
use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Container;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class ContainerListItem extends BaseListItem
{
    /** @var $item Container The associated list item */
    /** @var $list ContainerList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new Icon(
            $this->getStateIcon(),
            [
                'class' => [
                    'container-state-' . $this->item->state,
                    $this->item->ready ? 'container-ready' : 'container-not-ready'
                ]
            ]
        ));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            t('%s is %s', '<container> is <container_state>'),
            new Link($this->item->name, Links::container($this->item), ['class' => 'subject']),
            new HtmlElement('span', null, new Text($this->item->state))
        ));
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        $stateDetails = json_decode($this->item->state_details);
        if (isset($stateDetails->message)) {
            $main->addHtml(new HtmlElement('p', null, new Text($stateDetails->message)));
        }

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new HtmlElement(
            'div',
            null,
            new HorizontalKeyValue(t('Started'), new Icon($this->item->started ? 'check' : 'xmark')),
            new HorizontalKeyValue(t('Ready'), new Icon($this->item->ready ? 'check' : 'xmark'))
        ));
        $keyValue->addHtml($this->createStateDetails());
        $keyValue->addHtml(new VerticalKeyValue(t('Image'), $this->item->image));
        $keyValue->addHtml(new VerticalKeyValue(t('Restarts'), $this->item->restart_count));
        $main->addHtml($keyValue);
    }

    protected function getStateIcon(): string
    {
        switch ($this->item->state) {
            case Container::STATE_WAITING:
                return Icons::CONTAINER_WAITING;
            case Container::STATE_RUNNING:
                return Icons::CONTAINER_RUNNING;
            case Container::STATE_TERMINATED:
                return Icons::CONTAINER_TERMINATED;
            default:
                return Icons::BUG;
        }
    }

    protected function createStateDetails(): ValidHtml
    {
        $stateDetails = json_decode($this->item->state_details);

        switch ($this->item->state) {
            case Container::STATE_RUNNING:
                return new VerticalKeyValue(
                    t('Started At'),
                    new TimeAgo((new DateTime($stateDetails->startedAt))->getTimestamp())
                );
            case Container::STATE_TERMINATED:
            case Container::STATE_WAITING:
                return new HtmlElement(
                    'div',
                    null,
                    new VerticalKeyValue(t('Reason'), $stateDetails->reason)
                );
            default:
                return new FormattedString('Unknown state %s', $this->item->state);
        }
    }
}

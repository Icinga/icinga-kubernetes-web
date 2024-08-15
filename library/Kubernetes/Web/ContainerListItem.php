<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use DateTime;
use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class ContainerListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());

        $stateDetails = json_decode((string) $this->item->state_details);

        if (isset($stateDetails->finishedAt)) {
            $time = new DateTime($stateDetails->finishedAt);
        } elseif (isset($stateDetails->startedAt)) {
            $time = new DateTime($stateDetails->startedAt);
        } else {
            $time = null;
        }

        if (isset($time)) {
            $header->addHtml(new TimeAgo($time->getTimestamp()));
        }
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new Text($this->item->icinga_state_reason));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml(
            $this->createHeader(),
            $this->createCaption(),
            $this->createFooter()
        );
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $footer->addHtml(
            new HorizontalKeyValue($this->translate('Started'), Icons::ready($this->item->started)),
            new HorizontalKeyValue($this->translate('Ready'), Icons::ready($this->item->ready)),
            new HorizontalKeyValue(
                new Icon('arrows-spin', new Attributes(['title' => $this->translate('Restarts')])),
                new Text($this->item->restart_count)
            ),
            (new HorizontalKeyValue(new Text($this->translate('Image')), new Text($this->item->image)))
                ->addAttributes([
                    'class' => 'push-left container-image'
                ]),
            new HorizontalKeyValue(
                new Icon('download', new Attributes(['title' => $this->translate('Image Pull Policy')])),
                new Text($this->item->image_pull_policy)
            )
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<container> is <container_state>'),
            new Link(
                (new HtmlDocument())->addHtml(
                    new Icon('box'),
                    new Text($this->item->name)
                ),
                Links::container($this->item),
                ['class' => 'subject']
            ),
            new HtmlElement('span', new Attributes(['class' => 'icinga-state-text']), new Text($this->item->icinga_state))
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

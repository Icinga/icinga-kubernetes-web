<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\DefaultListItemCaption;
use Icinga\Module\Kubernetes\Common\DefaultListItemHeader;
use Icinga\Module\Kubernetes\Common\DefaultListItemMain;
use Icinga\Module\Kubernetes\Common\DefaultListItemVisual;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;

class CronJobListItem extends BaseListItem
{
    use Translation;
    use DefaultListItemHeader;
    use DefaultListItemCaption;
    use DefaultListItemMain;
    use DefaultListItemVisual;

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        if (isset($this->item->last_schedule_time)) {
            $lastScheduleTime = $this->item->last_schedule_time->format('Y-m-d H:i:s');
        } else {
            $lastScheduleTime = $this->translate('None');
        }

        if (isset($this->item->last_successful_time)) {
            $lastSuccessfulTime = $this->item->last_successful_time->format('Y-m-d H:i:s');
        } else {
            $lastSuccessfulTime = $this->translate('None');
        }

        $footer->addHtml(
            new HorizontalKeyValue($this->translate('Active'), $this->item->active),
            new HorizontalKeyValue($this->translate('Suspend'), Icons::ready($this->item->suspend)),
            (new HorizontalKeyValue($this->translate('Last Successful'), $lastSuccessfulTime))
                ->addAttributes(['class' => 'push-left']),
            new HorizontalKeyValue($this->translate('Last Scheduled'), $lastScheduleTime)
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(
            new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                new Text($this->item->namespace)
            ),
            new Link(
                (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-cronjob'])),
                    new Text($this->item->name)
                ),
                Links::cronjob($this->item),
                new Attributes(['class' => 'subject'])
            )
        );
    }
}

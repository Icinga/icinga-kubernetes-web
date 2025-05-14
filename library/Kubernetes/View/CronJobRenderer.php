<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;

class CronJobRenderer extends BaseResourceRenderer
{
    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(
            new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                new Text($item->namespace)
            ),
            new Link(
                (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-cronjob'])),
                    new Text($item->name)
                ),
                Links::cronjob($item),
                new Attributes(['class' => 'subject'])
            )
        );
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        if (isset($item->last_schedule_time)) {
            $lastScheduleTime = $item->last_schedule_time->format('Y-m-d H:i:s');
        } else {
            $lastScheduleTime = $this->translate('None');
        }

        if (isset($item->last_successful_time)) {
            $lastSuccessfulTime = $item->last_successful_time->format('Y-m-d H:i:s');
        } else {
            $lastSuccessfulTime = $this->translate('None');
        }

        $footer->addHtml(
            new HorizontalKeyValue($this->translate('Active'), $item->active),
            new HorizontalKeyValue($this->translate('Suspend'), Icons::ready($item->suspend)),
            (new HorizontalKeyValue($this->translate('Last Successful'), $lastSuccessfulTime))
                ->addAttributes(['class' => 'push-left']),
            new HorizontalKeyValue($this->translate('Last Scheduled'), $lastScheduleTime)
        );
    }
}

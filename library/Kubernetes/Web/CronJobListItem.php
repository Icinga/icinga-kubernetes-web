<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Common\States;
use Icinga\Module\Kubernetes\Model\CronJob;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class CronJobListItem extends BaseListItem
{
    /** @var $item CronJob The associated list item */
    /** @var $list CronJobList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $health = $this->getHealth();
        $visual->addHtml(new Icon(States::icon($health), ['class' => ['health-' . $health]]));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<cron_job> is <health>'),
            new Link(
                $this->item->name,
                Links::cronJob($this->item),
                ['class' => 'subject']
            ),
            Html::tag('span', ['class' => 'cron-job-text'], $this->getHealth())
        );

        $title->addHtml($content);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $header->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $lastScheduleTime = '-';
        if (isset($this->cronJob->last_schedule_time)) {
            $lastScheduleTime = $this->cronJob->last_schedule_time->format('Y-m-d H:i:s');
        }

        $main->add($this->createHeader());
        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue
            ->add(new VerticalKeyValue('Schedule', $this->item->schedule))
            ->add(new VerticalKeyValue('Suspend', $this->item->suspend))
            ->add(new VerticalKeyValue('Active', $this->item->active))
            ->add(new VerticalKeyValue('Last Schedule', $lastScheduleTime))
            ->add(new VerticalKeyValue('Namespace', $this->item->namespace));
        $main->add($keyValue);
    }

    protected function getHealth(): string
    {
        return States::HEALTHY;
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Common\States;
use Icinga\Module\Kubernetes\Model\Job;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

use const Grpc\STATUS_ABORTED;

class JobListItem extends BaseListItem
{
    /** @var $item Job The associated list item */
    /** @var $list JobList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $health = $this->getHealth();
        $visual->addHtml(new Icon(States::icon($health), ['class' => ['health-' . $health]]));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s is %s', '<job> is <health>'),
            new Link(
                $this->item->name,
                Links::job($this->item),
                ['class' => 'subject']
            ),
            Html::tag('span', ['class' => 'health-text'], $this->getHealth())
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
        $main->addHtml($this->createHeader());

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));

        $pods = new HtmlElement('div', new Attributes(['class' => 'pod-balls']));

        for ($i = 0; $i < $this->item->failed; $i++) {
            $pods->addHtml(new StateBall('critical', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $this->item->succeeded; $i++) {
            $pods->addHtml(new StateBall('ok', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $this->item->active; $i++) {
            $pods->addHtml(new StateBall('pending', StateBall::SIZE_MEDIUM));
        }

        $keyValue->add(new VerticalKeyValue('Pods', $pods));
        $keyValue->add(new VerticalKeyValue('Parallelism', $this->item->parallelism));
        $keyValue->add(new VerticalKeyValue('Completions', $this->item->completions));
        $keyValue->add(new VerticalKeyValue('Active', $this->item->active));
        $keyValue->add(new VerticalKeyValue('Succeeded', $this->item->succeeded));
        $keyValue->add(new VerticalKeyValue('Failed', $this->item->failed));
        $keyValue->add(new VerticalKeyValue('Namespace', $this->item->namespace));
        $main->addHtml($keyValue);
    }

    private function getHealth(): string
    {
        foreach ($this->item->condition as $jobCondition) {
            if ($jobCondition->type === "complete" && $jobCondition->status === "true") {
                return States::HEALTHY;
            } elseif ($jobCondition->type === "failed" && $jobCondition->status === "true") {
                return States::UNHEALTHY;
            } elseif ($jobCondition->type === "suspended" && $jobCondition->status === "true") {
                return States::UNDECIDABLE;
            }
        }
        return States::DEGRADED;
    }
}

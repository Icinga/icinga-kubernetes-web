<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Health;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Str;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class DaemonSetListItem extends BaseListItem
{
    /** @var $item DaemonSet The associated list item */
    /** @var $list DaemonSetList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $health = $this->getHealth();
        $visual->addHtml(new Icon(Health::icon($health), ['class' => ['health-' . $health]]));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            t('%s is %s', '<daemon_set> is <health>'),
            new Link($this->item->name, Links::daemonSet($this->item), ['class' => 'subject']),
            Html::tag('span', null, $this->getHealth())
        ));
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
        $header->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        $pods = new HtmlElement('div', new Attributes(['class' => 'pod-balls']));
        for ($i = 0; $i < $this->item->number_unavailable; $i++) {
            $pods->addHtml(new StateBall('critical', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $this->item->number_available; $i++) {
            $pods->addHtml(new StateBall('ok', StateBall::SIZE_MEDIUM));
        }
        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new VerticalKeyValue(t('Pods'), $pods));
        $keyValue->addHtml(new VerticalKeyValue(
            t('Update Strategy'),
            ucfirst(Str::camel($this->item->update_strategy))
        ));
        $keyValue->addHtml(new VerticalKeyValue(t('Min Ready Seconds'), $this->item->min_ready_seconds));
        $keyValue->addHtml(new VerticalKeyValue(t('Namespace'), $this->item->namespace));
        $main->addHtml($keyValue);
    }

    protected function getHealth(): string
    {
        if ($this->item->desired_number_scheduled < 1) {
            return Health::UNDECIDABLE;
        }

        switch (true) {
            case $this->item->number_available < 1:
                return Health::UNHEALTHY;
            case $this->item->number_unavailable > 1:
                return Health::DEGRADED;
            default:
                return Health::HEALTHY;
        }
    }
}

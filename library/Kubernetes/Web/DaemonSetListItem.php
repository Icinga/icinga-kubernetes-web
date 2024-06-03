<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Health;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class DaemonSetListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header
            ->addHtml($this->createTitle())
            ->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        $main->addHtml(new HtmlElement(
            'div',
            new Attributes(['class' => 'state-reason list']),
            Text::create($this->item->icinga_state_reason)
        ));

        $pods = new HtmlElement('div', new Attributes(['class' => 'pod-balls']));
        for ($i = 0; $i < $this->item->number_unavailable; $i++) {
            $pods->addHtml(new StateBall('critical', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $this->item->number_available; $i++) {
            $pods->addHtml(new StateBall('ok', StateBall::SIZE_MEDIUM));
        }
        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('Pods'), $pods));
        $keyValue->addHtml(new VerticalKeyValue(
            $this->translate('Update Strategy'),
            ucfirst(Str::camel($this->item->update_strategy))
        ));
        $keyValue->addHtml(new VerticalKeyValue(
            $this->translate('Min Ready Seconds'),
            $this->item->min_ready_seconds
        ));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('Namespace'), $this->item->namespace));
        $main->addHtml($keyValue);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<daemon_set> is <health>'),
            new Link($this->item->name, Links::daemonSet($this->item), ['class' => 'subject']),
            Html::tag('span', null, $this->item->icinga_state)
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

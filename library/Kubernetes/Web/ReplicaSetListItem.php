<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Health;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class ReplicaSetListItem extends BaseListItem
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

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $main->addHtml($keyValue);

        $desired = $this->item->desired_replicas;
        $unknown = 0;
        $available = $this->item->available_replicas;
        $pending = 0;
        $critical = $desired - $available;
        $pods = new HtmlElement('div', new Attributes(['class' => 'pod-balls']));
        for ($i = 0; $i < $critical; $i++) {
            $pods->addHtml(new StateBall('critical', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $pending; $i++) {
            $pods->addHtml(new StateBall('pending', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $unknown; $i++) {
            $pods->addHtml(new StateBall('unknown', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $available; $i++) {
            $pods->addHtml(new StateBall('ok', StateBall::SIZE_MEDIUM));
        }
        $keyValue->addHtml(new VerticalKeyValue('Pods', $pods));
        $keyValue->addHtml(new VerticalKeyValue('Min Ready Seconds', $this->item->min_ready_seconds));
        $keyValue->addHtml(new VerticalKeyValue('Namespace', $this->item->namespace));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            $this->translate('%s is %s', '<replica_set> is <health>'),
            new Link($this->item->name, Links::replicaSet($this->item), ['class' => 'subject']),
            Html::tag('span', null, $this->item->icinga_state)
        );

        $title->addHtml($content);
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

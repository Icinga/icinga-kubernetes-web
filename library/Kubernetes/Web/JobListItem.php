<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Health;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class JobListItem extends BaseListItem
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
        for ($i = 0; $i < $this->item->failed; $i++) {
            $pods->addHtml(new StateBall('critical', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $this->item->succeeded; $i++) {
            $pods->addHtml(new StateBall('ok', StateBall::SIZE_MEDIUM));
        }
        for ($i = 0; $i < $this->item->active; $i++) {
            $pods->addHtml(new StateBall('pending', StateBall::SIZE_MEDIUM));
        }
        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new VerticalKeyValue('Pods', $pods));
        $keyValue->addHtml(new VerticalKeyValue('Parallelism', $this->item->parallelism));
        $keyValue->addHtml(new VerticalKeyValue('Completions', $this->item->completions));
        $keyValue->addHtml(new VerticalKeyValue('Active', $this->item->active));
        $keyValue->addHtml(new VerticalKeyValue('Succeeded', $this->item->succeeded));
        $keyValue->addHtml(new VerticalKeyValue('Failed', $this->item->failed));
        $keyValue->addHtml(new VerticalKeyValue('Namespace', $this->item->namespace));
        $main->addHtml($keyValue);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<job> is <health>'),
            new Link($this->item->name, Links::job($this->item), ['class' => 'subject']),
            Html::tag('span', null, $this->item->icinga_state)
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

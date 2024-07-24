<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class DaemonSetListItem extends BaseListItem
{
    use Translation;

    public const UPDATE_STRATEGY_ICONS = [
        'RollingUpdate' => 'repeat',
        'OnDelete'      => 'trash'
    ];

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml(
            $this->createTitle(),
            new TimeAgo($this->item->created->getTimestamp())
        );
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
        $pods = (new ItemCountIndicator())
            ->addIndicator('critical', $this->item->number_unavailable)
            ->addIndicator('ok', $this->item->number_available);

        $footer->addHtml(
            (new HorizontalKeyValue(new HtmlElement('i', new Attributes(['class' => 'icon ikicon-kubernetes-pod'])), $pods))
                ->addAttributes([
                    'title' => sprintf(
                        $this->translate(
                            '%d %s available (%d unavailable)',
                            '%d:num_of_available_daemon_pods %s:daemon_pods_translation (%d:num_of_unavailable_daemon_pods)'
                        ),
                        $pods->getIndicator('ok'),
                        $this->translatePlural('daemon pod', 'daemon pods', $pods->getIndicator('ok')),
                        $pods->getIndicator('critical')
                    ),
                    'class' => 'pods-value'
                ]),
            (new Icon(static::UPDATE_STRATEGY_ICONS[$this->item->update_strategy]))
                ->addAttributes([
                    'title' => sprintf(
                        '%s: %s',
                        $this->translate('Update Strategy'),
                        $this->item->update_strategy
                    )
                ]),
            (new HorizontalKeyValue(new Icon('stopwatch'), $this->item->min_ready_seconds . 's'))
                ->addAttributes([
                    'title' => $this->translate('Min Ready Seconds')
                ])
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<daemon_set> is <icinga_state>'),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new Icon('folder-open'),
                    new Text($this->item->namespace)
                ),
                new Link(
                    $this->item->name,
                    Links::daemonSet($this->item),
                    ['class' => 'subject']
                )
            ],
            new HtmlElement(
                'span', new Attributes(['class' => 'icinga-state-text']), new Text($this->item->icinga_state)
            )
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

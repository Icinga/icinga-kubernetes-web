<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Format;
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

class StatefulSetListItem extends BaseListItem
{
    use Translation;

    public const UPDATE_STRATEGY_ICONS = [
        'RollingUpdate' => 'repeat',
        'OnDelete'      => 'trash'
    ];

    public const MANAGEMENT_POLICY_ICONS = [
        'OrderedReady' => 'shuffle',
        'Parallel'     => 'grip-lines'
    ];

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        match ($this->getViewMode()) {
            ViewModeSwitcher::VIEW_MODE_MINIMAL,
            ViewModeSwitcher::VIEW_MODE_COMMON   =>
            $header->addHtml(
                Html::tag(
                    'span',
                    Attributes::create(['class' => 'header-minimal']),
                    [
                        $this->createTitle(),
                        $this->createCaption()
                    ]
                )
            ),
            ViewModeSwitcher::VIEW_MODE_DETAILED =>
            $header->addHtml($this->createTitle()),
            default                              => null
        };

        $header->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new Text($this->item->icinga_state_reason));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        if ($this->getViewMode() === ViewModeSwitcher::VIEW_MODE_DETAILED) {
            $main->addHtml($this->createCaption());
        }

        if ($this->getViewMode() !== ViewModeSwitcher::VIEW_MODE_MINIMAL) {
            $main->addHtml($this->createFooter());
        }
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $pods = (new ItemCountIndicator())
            ->addIndicator('critical', $this->item->actual_replicas - $this->item->available_replicas)
            ->addIndicator('pending', $this->item->desired_replicas - $this->item->actual_replicas)
            ->addIndicator('ok', $this->item->available_replicas);

        $footer->addHtml(
            (new HorizontalKeyValue(
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
                $pods
            ))
                ->addAttributes([
                    'title' => sprintf(
                        $this->translate(
                            '%d %s available (%d unavailable)',
                            '%d:num_of_available_replicas %s:replicas_translation (%d:num_of_unavailable_replicas)'
                        ),
                        $pods->getIndicator('ok'),
                        $this->translatePlural('replica', 'replicas', $pods->getIndicator('ok')),
                        $pods->getIndicator('critical')
                    )
                ]),
            new HorizontalKeyValue(
                new Icon('stopwatch', ['title' => $this->translate('Min Ready Duration')]),
                Format::seconds($this->item->min_ready_seconds, $this->translate('None'))
            ),
            new HorizontalKeyValue(
                new Icon('retweet', ['title' => $this->translate('Update Strategy')]),
                $this->item->update_strategy
            ),
            new HorizontalKeyValue(
                new Icon('shuffle', ['title' => $this->translate('Pod Management Policy')]),
                $this->item->pod_management_policy
            ),
            (new HorizontalKeyValue(
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-service'])),
                $this->item->service_name
            ))
                ->addAttributes([
                    'class' => 'push-left',
                    'title' => $this->translate('Service Name'),
                ])
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<stateful_set> is <icinga_state>'),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text($this->item->namespace)
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new HtmlElement('i', new Attributes(['class' => 'icon kicon-stateful-set'])),
                        new Text($this->item->name)
                    ),
                    Links::statefulSet($this->item),
                    new Attributes(['class' => 'subject'])
                )
            ],
            new HtmlElement(
                'span',
                new Attributes(['class' => 'icinga-state-text']),
                new Text($this->item->icinga_state)
            )
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

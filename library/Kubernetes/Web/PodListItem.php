<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\InitContainer;
use Icinga\Module\Kubernetes\Model\SidecarContainer;
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

class PodListItem extends BaseListItem
{
    use Translation;

    public const QOS_ICONS = [
        'BestEffort' => 'eraser',
        'Burstable'  => 'life-ring',
        'Guaranteed' => 'heart-pulse'
    ];

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        if (in_array($this->getViewMode(), [ViewModeSwitcher::VIEW_MODE_MINIMAL, ViewModeSwitcher::VIEW_MODE_COMMON])) {
            $header->addHtml(
                Html::tag(
                    'span',
                    Attributes::create(['class' => 'header-minimal']),
                    [
                        $this->createTitle(),
                        $this->createCaption()
                    ]
                )
            );
        } elseif ($this->getViewMode() === ViewModeSwitcher::VIEW_MODE_DETAILED) {
            $header->addHtml($this->createTitle());
        }

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
        $containerRestarts = 0;
        $containers = new ItemCountIndicator();
        $containers->setStyle($containers::STYLE_BOX);

        /** @var InitContainer $initContainer */
        foreach ($this->item->init_container as $container) {
            if ($container->icinga_state !== 'ok') {
                $containers->addIndicator($container->icinga_state, 1);
            }
        }

        /** @var SidecarContainer $container */
        foreach ($this->item->sidecar_container as $container) {
            $containerRestarts += $container->restart_count;
            $containers->addIndicator($container->icinga_state, 1);
        }

        /** @var Container $container */
        foreach ($this->item->container as $container) {
            $containerRestarts += $container->restart_count;
            $containers->addIndicator($container->icinga_state, 1);
        }

        $footer->addHtml(
            (new HorizontalKeyValue(new Icon('box'), $containers))
                ->addAttributes([
                    'title' => sprintf(
                        $this->translate(
                            '%d %s running (%d not running)',
                            '%d:num_of_running_containers %s:containers_translation'
                            . ' (%d:num_of_not_running_containers)'
                        ),
                        $containers->getIndicator('ok'),
                        $this->translatePlural('container', 'containers', $containers->getIndicator('ok')),
                        $containers->getIndicator('critical')
                    )
                ]),
            new HorizontalKeyValue(
                new Icon('arrows-spin', ['title' => $this->translate('Container Restarts')]),
                $containerRestarts
            ),
            new HorizontalKeyValue(
                (new Icon('recycle'))->addAttributes(['title' => $this->translate('Restart Policy')]),
                $this->item->restart_policy
            ),
            new HorizontalKeyValue(
                (new Icon('life-ring'))->addAttributes(['title' => $this->translate('Quality of Service')]),
                $this->item->qos
            ),
            (new HorizontalKeyValue(
                $this->translate('IP'),
                $this->item->ip ?? $this->translate('None')
            ))
                ->addAttributes(['class' => 'push-left']),
            new HorizontalKeyValue(new Icon('share-nodes'), $this->item->node_name ?? $this->translate('None'))
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<pod> is <icinga_state>'),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text($this->item->namespace)
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
                        new Text($this->item->name)
                    ),
                    Links::pod($this->item),
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

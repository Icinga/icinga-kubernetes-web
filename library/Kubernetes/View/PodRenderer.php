<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\InitContainer;
use Icinga\Module\Kubernetes\Model\SidecarContainer;
use Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason\PodIcingaStateReason;
use Icinga\Module\Kubernetes\Web\Widget\ItemCountIndicator;
use Icinga\Module\Kubernetes\Web\Widget\KIcon;
use ipl\Html\HtmlDocument;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;

class PodRenderer extends BaseResourceRenderer
{
    public const QOS_ICONS = [
        'BestEffort' => 'eraser',
        'Burstable'  => 'life-ring',
        'Guaranteed' => 'heart-pulse'
    ];

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(new PodIcingaStateReason($item->uuid, $item->icinga_state_reason, $item->icinga_state));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $containerRestarts = 0;
        $containers = new ItemCountIndicator();
        $containers->setStyle($containers::STYLE_BOX);

        /** @var InitContainer $initContainer */
        foreach ($item->init_container as $container) {
            if ($container->icinga_state !== 'ok') {
                $containers->addIndicator($container->icinga_state, 1);
            }
        }

        /** @var SidecarContainer $container */
        foreach ($item->sidecar_container as $container) {
            $containerRestarts += $container->restart_count;
            $containers->addIndicator($container->icinga_state, 1);
        }

        /** @var Container $container */
        foreach ($item->container as $container) {
            $containerRestarts += $container->restart_count;
            $containers->addIndicator($container->icinga_state, 1);
        }

        $footer->addHtml(
            (new HorizontalKeyValue(
                new KIcon('container'),
                $containers
            ))
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
                $item->restart_policy
            ),
            new HorizontalKeyValue(
                (new Icon(static::QOS_ICONS[$item->qos]))
                    ->addAttributes(['title' => $this->translate('Quality of Service')]),
                $item->qos
            ),
            (new HorizontalKeyValue(
                $this->translate('IP'),
                $item->ip ?? $this->translate('None')
            ))
                ->addAttributes(['class' => 'push-left']),
            new HorizontalKeyValue(new Icon('share-nodes'), $item->node_name ?? $this->translate('None'))
        );
    }
}

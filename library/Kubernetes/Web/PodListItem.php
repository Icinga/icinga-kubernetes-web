<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\DefaultListItemCaption;
use Icinga\Module\Kubernetes\Common\DefaultListItemHeader;
use Icinga\Module\Kubernetes\Common\DefaultListItemMain;
use Icinga\Module\Kubernetes\Common\DefaultListItemTitle;
use Icinga\Module\Kubernetes\Common\DefaultListItemVisual;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\InitContainer;
use Icinga\Module\Kubernetes\Model\SidecarContainer;
use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;

class PodListItem extends BaseListItem
{
    use Translation;
    use DefaultListItemHeader;
    use DefaultListItemCaption;
    use DefaultListItemMain;
    use DefaultListItemTitle;
    use DefaultListItemVisual;

    public const QOS_ICONS = [
        'BestEffort' => 'eraser',
        'Burstable'  => 'life-ring',
        'Guaranteed' => 'heart-pulse'
    ];

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
//            (new HorizontalKeyValue(
//                $this->translate('IP'),
//                $this->item->ip ?? $this->translate('None')
//            ))
//                ->addAttributes(['class' => 'push-left']),
//            new HorizontalKeyValue(new Icon('share-nodes'), $this->item->node_name ?? $this->translate('None'))
        );
    }
}

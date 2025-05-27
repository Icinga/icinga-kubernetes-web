<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\Secret;
use Icinga\Module\Kubernetes\Web\Widget\MoveFavoriteForm;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Layout\ItemLayout;
use ipl\Web\Widget\Icon;
use Ramsey\Uuid\Uuid;

class ResourceDefaultItemLayout extends ItemLayout
{
    protected function assembleMain(HtmlDocument $container): void
    {
        switch (true) {
            case $this->item instanceof Ingress:
            case $this->item instanceof PersistentVolume:
            case $this->item instanceof PersistentVolumeClaim:
            case $this->item instanceof Secret:
                $this->registerHeader($container);
                $this->registerFooter($container);

                break;
            default:
                parent::assembleMain($container);
        }

        if (isset($this->item->favorite->priority)) {
            // Add background span with same color as the main background to ensure correct
            // coloring in both dark and light mode.
            $reorderHandle = new HtmlElement(
                'span',
                Attributes::create(['class' => 'reorder-handle-background']),
                new HtmlElement(
                    'span',
                    Attributes::create(['class' => 'reorder-handle-container']),
                    new Icon('bars', Attributes::create(['data-drag-initiator' => '']))
                )
            );
            $container->addHtml($reorderHandle);
        }
    }

    protected function assembleHeader(HtmlDocument $container): void
    {
        switch (true) {
            case $this->item instanceof Ingress:
            case $this->item instanceof PersistentVolume:
            case $this->item instanceof PersistentVolumeClaim:
            case $this->item instanceof Secret:
                $this->registerTitle($container);
                $this->registerExtendedInfo($container);

                break;
            default:
                parent::assembleHeader($container);
        }
    }

    protected function assemble(): void
    {
        if (isset($this->item->favorite->priority)) {
            $this->addHtml(
                (new MoveFavoriteForm())
                    ->setAction(
                        Links::moveFavorite(Factory::canonicalizeKind($this->item->getTableAlias()))->getAbsoluteUrl()
                    )
                    ->populate([
                        'uuid'     => Uuid::fromBytes($this->item->uuid)->toString(),
                        'priority' => $this->item->favorite->priority,
                    ])
                    ->addAttributes(['data-base-target' => '_self']),
            );
        }

        parent::assemble();
    }
}

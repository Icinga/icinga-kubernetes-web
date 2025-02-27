<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Web\Factory;
use Icinga\Module\Kubernetes\Web\MoveFavoriteForm;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Layout\MinimalItemLayout;
use ipl\Web\Widget\Icon;
use Ramsey\Uuid\Uuid;

class ResourceMinimalItemLayout extends MinimalItemLayout
{
    protected function assembleMain(HtmlDocument $container): void
    {
        parent::assembleMain($container);

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

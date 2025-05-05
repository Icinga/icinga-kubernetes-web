<?php

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Web\ViewModeSwitcher;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;

trait DefaultListItemMain
{
    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        if ($this->viewMode === ViewModeSwitcher::VIEW_MODE_DETAILED) {
            $main->addHtml($this->createCaption());
        }

        if ($this->viewMode !== ViewModeSwitcher::VIEW_MODE_MINIMAL) {
            $main->addHtml($this->createFooter());
        }

        if (isset($this->item->favorite->priority)) {
            // Add background span with same color as the main background to ensure correct
            // coloring in both dark and light mode.
            $reorderHandle = Html::tag(
                'span',
                Attributes::create(['class' => 'reorder-handle-background']),
                Html::tag(
                    'span',
                    Attributes::create(['class' => 'reorder-handle-container']),
                    new Icon('bars', Attributes::create(['data-drag-initiator' => '']))
                )
            );
            $this->addHtml($reorderHandle);
        }
    }
}

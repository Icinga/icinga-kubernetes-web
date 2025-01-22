<?php

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Web\ViewModeSwitcher;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\TimeAgo;

trait DefaultListItemHeader
{
    protected function assembleHeader(BaseHtmlElement $header): void
    {
        match ($this->viewMode) {
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
}

<?php

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Web\ViewModeSwitcher;
use ipl\Html\BaseHtmlElement;

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
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use ipl\Html\HtmlDocument;
use ipl\Web\Layout\ItemLayout;

class ConditionItemLayout extends ItemLayout
{
    protected function assembleMain(HtmlDocument $container): void
    {
        $this->registerHeader($container);
    }
}

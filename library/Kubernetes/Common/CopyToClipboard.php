<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use ipl\Html\BaseHtmlElement;

abstract class CopyToClipboard
{
    public static function attachTo(BaseHtmlElement $source): BaseHtmlElement
    {
        \ipl\Web\Widget\CopyToClipboard::attachTo($source);

        return $source;
    }
}

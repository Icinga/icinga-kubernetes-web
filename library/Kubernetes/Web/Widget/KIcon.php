<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

use ipl\Html\BaseHtmlElement;

class KIcon extends BaseHtmlElement
{
    protected $tag = 'i';

    protected $defaultAttributes = ['class' => 'icon'];

    public function __construct(string $icon)
    {
        $this->addAttributes(['class' => 'kicon-' . strtolower($icon)]);
    }
}

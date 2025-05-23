<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlString;
use ipl\Web\Widget\CopyToClipboard;

class Note extends BaseHtmlElement
{
    protected $tag = 'pre';

    public function __construct(protected string $note)
    {
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlString($this->note));

        CopyToClipboard::attachTo($this);
    }
}

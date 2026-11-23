<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class HighlightDelta extends BaseHtmlElement
{
    protected $tag = 'span';

    public function __construct(
        protected string $long,
        protected string $short,
        ?Attributes $attributes = null
    ) {

        $this->addAttributes($attributes);
    }

    public function assemble(): void
    {
        if (! str_contains($this->long, $this->short)) {
            $this->addHtml(new Text($this->long));
            return;
        }
        $this->addHtml(
            new Text($this->short),
            new HtmlElement(
                'span',
                new Attributes(['class' => 'highlight-text']),
                new Text(str_replace($this->short, '', $this->long))
            )
        );
    }
}

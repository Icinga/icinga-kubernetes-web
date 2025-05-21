<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class HighlightDelta extends BaseHtmlElement
{
    protected $tag = 'span';

    public function __construct(
        protected string $long,
        protected string $short
    ) {
        if (!str_contains($long, $short)) {
            throw new InvalidArgumentException('Short name must be part of long name');
        }
    }

    public function assemble(): void
    {
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

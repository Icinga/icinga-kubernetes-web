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
        $prefix = $this->short;
        if (strlen($prefix) > 58 && preg_match('/\A[a-z0-9]{5}\z/', substr($this->long, 58))) {
            // Kubernetes truncates generated Pod name prefixes to 58 characters.
            $prefix = substr($prefix, 0, 58);
        }

        if (! str_starts_with($this->long, $prefix)) {
            $this->addHtml(new Text($this->long));

            return;
        }

        $this->addHtml(
            new Text($prefix),
            new HtmlElement(
                'span',
                new Attributes(['class' => 'highlight-text']),
                new Text(substr($this->long, strlen($prefix)))
            )
        );
    }
}

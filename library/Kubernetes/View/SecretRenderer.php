<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Web\KIcon;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;

class SecretRenderer extends BaseResourceRenderer
{
    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $footer->addHtml(new HorizontalKeyValue($this->translate('Type'), $item->type));
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(
            new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                new KIcon('namespace'),
                new Text($item->namespace)
            ),
            new Link(
                (new HtmlDocument())->addHtml(
                    new KIcon('secret'),
                    new Text($item->name)
                ),
                Links::secret($item),
                new Attributes(['class' => 'subject'])
            )
        );
    }
}

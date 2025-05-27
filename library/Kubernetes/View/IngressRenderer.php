<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Web\Widget\KIcon;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

class IngressRenderer extends BaseResourceRenderer
{
    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        // TODO add icinga state then remove this function
        $visual->addHtml(new StateBall('none', StateBall::SIZE_MEDIUM));
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
    }

    public function assembleFooter($item, $footer, string $layout): void
    {
        $hosts = [];
        foreach ($item->ingress_rule as $rule) {
            if ($rule->host !== null) {
                $hosts[] = $rule->host;
            }
        }

        $footer->addHtml(
            new HorizontalKeyValue(
                $this->translate('Host'),
                ! empty($hosts) ? implode(', ', $hosts) : '-'
            )
        );
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
                    new KIcon('ingress'),
                    new Text($item->name)
                ),
                Links::ingress($item),
                new Attributes(['class' => 'subject'])
            )
        );
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Web\Factory;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use Ramsey\Uuid\Uuid;

class EventRenderer extends BaseResourceRenderer
{
    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $visual->addHtml(match ($item->type) {
            'Warning' => new StateBall('warning', StateBall::SIZE_MEDIUM),
            default   => new StateBall('none', StateBall::SIZE_MEDIUM)
        });
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(new Text($item->note));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(
            new Link($item->reason, Links::event($item), new Attributes(['class' => 'subject']))
        );

        $kind = strtolower($item->reference_kind);

        $icon = Factory::createIcon($kind);
        $url = Factory::createDetailUrl($kind);

        if ($url !== null) {
            $content = new HtmlDocument();

            if ($icon !== null) {
                $content->addHtml($icon);
            }

            $content->addHtml(new Text($item->reference_name));

            $referent = new Link(
                $content,
                $url->addParams(['id' => (string) Uuid::fromBytes($item->reference_uuid)]),
                new Attributes(['class' => 'subject'])
            );
        } else {
            $referent = new HtmlElement(
                'span',
                new Attributes(['class' => 'subject']),
                new Text($item->reference_name)
            );
        }

        if (isset($item->reference_namespace)) {
            $referent->prependHtml(new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                Factory::createIcon('namespace'),
                new Text($item->reference_namespace)
            ));
        }

        $title->addHtml($referent);
    }
}

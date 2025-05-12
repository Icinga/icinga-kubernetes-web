<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

class ServiceRenderer extends BaseResourceRenderer
{
    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        // TODO add icinga state then remove this function
        $visual->addHtml(new StateBall('none', StateBall::SIZE_MEDIUM));
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        // TODO add state reason then remove this function
        $caption->addHtml(new Text('Placeholder for Icinga State Reason'));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $footer->addHtml(
            new HorizontalKeyValue($this->translate('Type'), $item->type),
            (new HorizontalKeyValue($this->translate('Cluster IP'), $item->cluster_ip))
                ->addAttributes(['class' => 'push-left'])
        );
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(
            new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                new Text($item->namespace)
            ),
            new Link(
                (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-service'])),
                    new Text($item->name)
                ),
                Links::service($item),
                new Attributes(['class' => 'subject'])
            )
        );
    }
}

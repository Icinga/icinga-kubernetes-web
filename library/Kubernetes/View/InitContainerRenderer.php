<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\Text;
use ipl\Web\Url;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;

class InitContainerRenderer extends ContainerRenderer
{
    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $footer->addHtml(
            (new HorizontalKeyValue(new Text($this->translate('Image')), new Text($item->image)))
                ->addAttributes([
                    'class' => 'push-left container-image'
                ]),
            new HorizontalKeyValue(
                new Icon('download', new Attributes(['title' => $this->translate('Image Pull Policy')])),
                new Text($item->image_pull_policy)
            )
        );
    }

    protected function getDetailUrl($item): Url
    {
        return Links::initcontainer($item);
    }
}

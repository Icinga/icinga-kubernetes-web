<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Web\KIcon;
use Icinga\Module\Kubernetes\Web\ServiceIcingaStateReason;
use Icinga\Module\Kubernetes\Web\WorkloadIcingaStateReason;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use SplObjectStorage;

class ServiceRenderer extends BaseResourceRenderer
{
    protected $cache;
    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $this->cache ??= new SplObjectStorage();
        $this->cache[$item] ??= new ServiceIcingaStateReason($item);

        $visual->addHtml(new StateBall($this->cache[$item]->getState(), StateBall::SIZE_MEDIUM));
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $this->cache ??= new SplObjectStorage();
        $this->cache[$item] ??= new ServiceIcingaStateReason($item);

        $caption->addHtml($this->cache[$item]);
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
                new KIcon('namespace'),
                new Text($item->namespace)
            ),
            new Link(
                (new HtmlDocument())->addHtml(
                    new KIcon('service'),
                    new Text($item->name)
                ),
                Links::service($item),
                new Attributes(['class' => 'subject'])
            )
        );
    }
}

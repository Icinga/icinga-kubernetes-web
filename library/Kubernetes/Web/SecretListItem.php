<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;

class SecretListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        match ($this->viewMode) {
            ViewModeSwitcher::VIEW_MODE_MINIMAL =>
            $header->addHtml(
                Html::tag(
                    'span',
                    Attributes::create(['class' => 'header-minimal']),
                    [
                        $this->createTitle(),
                        $this->createCaption()
                    ]
                )
            ),
            ViewModeSwitcher::VIEW_MODE_COMMON  =>
            $header->addHtml($this->createTitle()),
            default                             => null
        };

        $header->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        if ($this->viewMode === ViewModeSwitcher::VIEW_MODE_COMMON) {
            $main->addHtml($this->createFooter());
        }
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $footer->addHtml(new HorizontalKeyValue($this->translate('Type'), $this->item->type));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(
            new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                new Text($this->item->namespace)
            ),
            new Link(
                (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-secret'])),
                    new Text($this->item->name)
                ),
                Links::secret($this->item),
                new Attributes(['class' => 'subject'])
            )
        );
    }
}

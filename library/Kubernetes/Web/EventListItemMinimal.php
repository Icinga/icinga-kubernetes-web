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
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use Ramsey\Uuid\Uuid;

class EventListItemMinimal extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml(
            Html::tag(
                'span',
                Attributes::create(['class' => 'header-minimal']),
                [
                    $this->createTitle(),
                    $this->createCaption()
                ]
            ),
            new TimeAgo($this->item->last_seen->getTimestamp())
        );
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new Text($this->item->note));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml(
            $this->createHeader(),
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(
            new Link($this->item->reason, Links::event($this->item), new Attributes(['class' => 'subject']))
        );

        $kind = strtolower($this->item->reference_kind);

        $icon = Factory::createIcon($kind);
        $url = Factory::createDetailUrl($kind);

        if ($url !== null) {
            $content = new HtmlDocument();

            if ($icon !== null) {
                $content->addHtml($icon);
            }

            $content->addHtml(new Text($this->item->reference_name));

            $referent = new Link(
                $content,
                $url->addParams(['id' => (string) Uuid::fromBytes($this->item->reference_uuid)]),
                new Attributes(['class' => 'subject'])
            );
        } else {
            $referent = new HtmlElement(
                'span',
                new Attributes(['class' => 'subject']),
                new Text($this->item->reference_name)
            );
        }

        if (isset($this->item->reference_namespace)) {
            $referent->prependHtml(new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                Factory::createIcon('namespace'),
                new Text($this->item->reference_namespace)
            ));
        }

        $title->addHtml($referent);
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $typeVisual = $this->createTypeVisual();
        if ($typeVisual !== null) {
            $visual->addHtml($typeVisual);
        }
    }

    protected function createTypeVisual(): ?ValidHtml
    {
        return match ($this->item->type) {
            'Warning' => new StateBall('warning', StateBall::SIZE_MEDIUM),
            default   => new StateBall('none', StateBall::SIZE_MEDIUM)
        };
    }
}

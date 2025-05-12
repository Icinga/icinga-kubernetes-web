<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\TimeAgo;

class ConditionRenderer implements ItemRenderer
{
    /** @var callable */
    protected $getVisualFunc;

    public function __construct(callable $temp)
    {
        $this->getVisualFunc = $temp;
    }

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        [$status, $icon] = call_user_func($this->getVisualFunc, $item->status, $item->type);

        /** @var HtmlElement $visual */
        $visual->addAttributes(['class' => $status]);
        $visual->addHtml(new Icon($icon));
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(new HtmlElement('h3', null, new Text($item->type)));
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        $info->addHtml(new TimeAgo($item->last_transition->getTimestamp()));
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        return false;
    }
}

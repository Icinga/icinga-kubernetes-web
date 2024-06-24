<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;

class Labels extends BaseHtmlElement
{
    use Translation;

    protected $defaultAttributes = ['class' => 'labels'];

    protected $labels;

    protected $tag = 'section';

    public function __construct(iterable $labels)
    {
        $this->labels = $labels;
    }

    protected function assemble()
    {
        $listItems = [];
        $sortItems = [];
        $previousTitle = "";
        $titleAdded = false;

        foreach ($this->labels as $label) {
            $labelParts = explode('/', $label->name);
            $currentTitle = count($labelParts) > 1 ? $labelParts[0] : "-";
            $name = count($labelParts) > 1 ? $labelParts[1] : $label->name;

            if ($currentTitle !== $previousTitle) {
                if ($currentTitle !== "-") {
                    $listItems[] = $this->createTitleItem($currentTitle);
                } elseif (! $titleAdded){
                    $sortItems[] = $this->createTitleItem($currentTitle);
                    $titleAdded = true;
                }
                $previousTitle = $currentTitle;
            }

            $listItem = $this->createListItem($name, $label->value);

            if ($currentTitle !== "-") {
                $listItems[] = $listItem;
            } else {
                $sortItems[] = $listItem;
            }
        }

        $content = new HtmlElement('ul', new Attributes(['class' => 'labels']), ...$listItems, ...$sortItems);

        $this->addWrapper(
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Labels'))),
                $content
            )
        );
    }

    private function createTitleItem($title)
    {
        return new HtmlElement(
            'li', null,
            new HtmlElement('span', new Attributes(['class' => 'title']), new Text($title))
        );
    }

    private function createListItem($name, $value)
    {
        return  new HtmlElement(
            'ul', null,
            new HtmlElement(
                'li', null, new HorizontalKeyValue($name, $value)
            )
        );
    }
}

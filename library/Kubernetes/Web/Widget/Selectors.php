<?php

namespace Icinga\Module\Kubernetes\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;

use function Icinga\Module\Kubernetes\yield_iterable;

class Selectors extends BaseHtmlElement
{
    use Translation;

    protected iterable $selectors;

    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'selectors'];

    public function __construct(iterable $selectors)
    {
        $this->selectors = $selectors;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Selectors'))));

        $selectors = yield_iterable($this->selectors);
        $content = [];
        if ($selectors->valid()) {
            foreach ($this->categorizeSelectors($selectors) as $title => $selectors) {
                $content[] = new HtmlElement(
                    'li',
                    null,
                    new HtmlElement(
                        'span',
                        new Attributes(['class' => 'title']),
                        new Text($title)
                    ),
                    new HtmlElement(
                        'ul',
                        null,
                        ...array_map(
                            fn($name, $value) => new HtmlElement(
                                'li',
                                null,
                                new HorizontalKeyValue($name, $value)
                            ),
                            array_keys($selectors),
                            $selectors
                        )
                    )
                );
            }

            $this->addHtml(
                new HtmlElement(
                    'div',
                    new Attributes([
                        'class'               => 'collapsible',
                        'data-visible-height' => 100
                    ]),
                    new HtmlElement('ul', null, ...$content)
                )
            );
        } else {
            $this->addHtml(new EmptyState($this->translate('No items to display.')));
        }
    }

    private function categorizeSelectors(iterable $selectors): array
    {
        $categorized = [];

        foreach ($selectors as $selector) {
            [$prefix, $name] = Str::symmetricSplit($selector->name, '/', 2);
            $group = $name ? $prefix : '-';
            $categorized[$group][$name ?: $prefix] = $selector->value;
        }

        uksort($categorized, fn($a, $b) => $a === '-' ? 1 : ($b === '-' ? -1 : strcmp($a, $b)));

        return $categorized;
    }
}

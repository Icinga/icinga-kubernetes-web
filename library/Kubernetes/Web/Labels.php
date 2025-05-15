<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;

use function Icinga\Module\Kubernetes\yield_iterable;

class Labels extends BaseHtmlElement
{
    use Translation;

    protected iterable $labels;

    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'labels'];

    public function __construct(iterable $labels)
    {
        $this->labels = $labels;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Labels'))));

        $labels = yield_iterable($this->labels);
        $content = [];
        if ($labels->valid()) {
            foreach ($this->categorizeLabels($labels) as $title => $labels) {
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
                            array_keys($labels),
                            $labels
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
            $this->addHtml(new EmptyState($this->translate('No items found.')));
        }
    }

    private function categorizeLabels(iterable $labels): array
    {
        $categorized = [];

        foreach ($labels as $label) {
            [$prefix, $name] = Str::symmetricSplit($label->name, '/', 2);
            $group = $name ? $prefix : '-';
            $categorized[$group][$name ?: $prefix] = $label->value;
        }

        uksort($categorized, fn($a, $b) => $a === '-' ? 1 : ($b === '-' ? -1 : strcmp($a, $b)));

        return $categorized;
    }
}

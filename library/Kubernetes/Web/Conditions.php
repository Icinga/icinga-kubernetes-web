<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\TimeAgo;

abstract class Conditions extends HtmlDocument
{
    use Translation;

    abstract protected function getConditions(): iterable;

    abstract protected function getVisual(string $status, string $type): array;

    protected function assemble(): void
    {
        $conditions = $this->getConditions();
        if (empty($conditions)) {
            return;
        }

        $listItems = [];

        foreach ($conditions as $condition) {
            $message = $condition->reason;
            $message .= $condition->message ? ': ' . $condition->message : '';

            [$status, $icon] = $this->getVisual($condition->status, $condition->type);

            $listItem = new HtmlElement(
                'li',
                new Attributes(['class' => 'list-item']),
                new HtmlElement(
                    'div',
                    new Attributes(['class' => 'visual ' . $status]),
                    new Icon($icon)
                )
            );

            $main = new HtmlElement(
                'div',
                new Attributes(['class' => 'main']),
                new HtmlElement(
                    'header',
                    null,
                    new HtmlElement('h3', null, new Text($condition->type)),
                    new TimeAgo($condition->last_transition->getTimestamp())
                )
            );

            if (! empty($message)) {
                $caption = new HtmlElement(
                    'section',
                    null,
                    new IcingaStateReason($message)
                );
                $main->addHtml($caption);
            }

            $listItem->addHtml($main);
            $listItems[] = $listItem;
        }

        $this->addWrapper(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text($this->translate('Conditions'))),
            new HtmlElement('ul', new Attributes(['class' => 'conditions item-list']), ...$listItems)
        ));
    }
}

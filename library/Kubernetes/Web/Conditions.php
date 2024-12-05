<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\TimeAgo;

use function Icinga\Module\Kubernetes\yield_iterable;

abstract class Conditions extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'conditions'];

    abstract protected function getConditions(): iterable;

    abstract protected function getVisual(string $status, string $type): array;

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Conditions'))));

        $listItems = [];

        $conditions = yield_iterable($this->getConditions());
        if ($conditions->valid()) {
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
                        'div',
                        null,
                        new IcingaStateReason($message)
                    );
                    $main->addHtml($caption);
                }

                $listItem->addHtml($main);
                $listItems[] = $listItem;
            }

            $this->addHtml(new HtmlElement('ul', new Attributes(['class' => 'conditions item-list']), ...$listItems));
        } else {
            $this->addHtml(new EmptyState($this->translate('No items to display')));
        }
    }
}

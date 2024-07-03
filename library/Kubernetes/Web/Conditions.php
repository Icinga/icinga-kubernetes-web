<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
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

    abstract protected function getConditions();

    abstract protected function getVisual($status, $type): array;

    public function assemble()
    {
        $conditions = $this->getConditions();
        if (empty($conditions)) {
            return;
        }

        $listItems = [];

        foreach ($conditions as $condition) {
            $pluginOutput = $condition->reason;
            $pluginOutput .= $condition->message ? ": " . $condition->message : '';

            [$status, $icon] = $this->getVisual($condition->status, $condition->type);

            $listItem = new HtmlElement('li', new Attributes(['class' => 'list-item']),
                new HtmlElement(
                    'div',
                    new Attributes(['class' => 'visual ' . $status]),
                    new Icon($icon)
                ));

            $main = new HtmlElement('div', new Attributes(['class' => 'main']),
                new HtmlElement(
                    'header', null,
                    new HtmlElement('h3', null, new Text($condition->type)),
                    new TimeAgo($condition->last_transition->getTimestamp())
                ));

            if (! empty($pluginOutput)) {
                $caption = new HtmlElement('section', new Attributes(['class' => 'caption']),
                    new PluginOutputContainer(new PluginOutput($pluginOutput))
                );
                $main->addHtml($caption);
            }

            $listItem->addHtml($main);
            $listItems[] = $listItem;
        }

        $content = new HtmlElement('ul', new Attributes(['class' => 'condition-list item-list']), ...$listItems);

        $this->addWrapper(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text($this->translate('Conditions'))),
            $content
        ));
    }
}

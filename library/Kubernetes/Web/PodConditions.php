<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Kubernetes\Model\PodCondition;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\TimeAgo;

class PodConditions extends HtmlDocument
{
    use Translation;

    protected const SORT_ORDER = ["Ready", "ContainersReady", "PodReadyToStartContainers", "Initialized", "PodScheduled"];

    protected $pod;

    public function __construct($pod)
    {
        $this->pod = $pod;
    }

    public function assemble()
    {
        $listItems = [];
        $conditions = $this->processConditions();

        usort($conditions, function ($a, $b) {
            return array_search($a->type, static::SORT_ORDER) <=> array_search($b->type, static::SORT_ORDER);
        });

        foreach ($conditions as $condition) {
            $pluginOutput = $condition->reason;
            $pluginOutput .= $condition->message ? ": " . $condition->message : '';

            [$status, $icon] = $this->getVisual($condition->status);

            $listItem = new HtmlElement('li', new Attributes(['class' => 'list-item']),
                new HtmlElement(
                    'div',
                    new Attributes(['class' => 'visual ' . $status]),
                    new Icon($icon)
                ),
                new HtmlElement('div', new Attributes(['class' => 'main']),
                    new HtmlElement(
                        'header', null,
                        new HtmlElement('h3', null, new Text($condition->type)),
                        new TimeAgo($condition->last_transition->getTimestamp())
                    ),
                    new HtmlElement('section', new Attributes(['class' => 'caption']),
                        new PluginOutputContainer(new PluginOutput($pluginOutput))
                    )
                )
            );

            $listItems[] = $listItem;
        }

        $content = new HtmlElement('ul', new Attributes(['class' => 'condition-list item-list']), ...$listItems);

        $this->addWrapper(
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Conditions'))),
                $content
            )
        );
    }

    private function processConditions()
    {
        $processedConditions = [];
        $isCompletedConditionAdded = false;

        foreach ($this->pod->condition as $condition) {
            if ($condition->type === "Ready") {
                $lastTransition = $condition->last_transition;
            }

            if ($this->pod->phase === "succeeded") {
                if (! $isCompletedConditionAdded && isset($lastTransition)) {
                    $completedCondition = $this->createCompletedCondition($lastTransition);
                    $processedConditions[] = $completedCondition;
                    $isCompletedConditionAdded = true;
                }
                if (in_array($condition->type, ["PodScheduled", "Initialized"])) {
                    $processedConditions[] = $condition;
                }
            } else {
                $processedConditions[] = $condition;
            }
        }

        return $processedConditions;
    }

    private function createCompletedCondition($lastTransition): PodCondition
    {
        $completed = new PodCondition();
        $completed->type = "Completed";
        $completed->reason = "All containers have been terminated successfully and will not be restarted.";
        $completed->message = "";
        $completed->status = "True";
        $completed->last_transition = $lastTransition;

        return $completed;
    }

    private function getVisual($status): array
    {
        switch ($status) {
            case "True":
                return ['success', 'check-circle'];
            case "False":
                return ['error', 'times-circle'];
            default:
                return ['unknown', 'question-circle'];
        }
    }
}

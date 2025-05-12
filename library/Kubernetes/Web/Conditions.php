<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Web\ItemList\ConditionList;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;

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

        $conditions = yield_iterable($this->getConditions());

        $this->addHtml(new ConditionList($conditions, function (string $status, string $type) {
            return $this->getVisual($status, $type);
        }));
    }
}

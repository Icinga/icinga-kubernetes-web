<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
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
        if ($labels->valid()) {
            foreach ($labels as $label) {
                $this->addHtml(new HorizontalKeyValue($label->name, $label->value));
            }
        } else {
            $this->addHtml(new EmptyState($this->translate('No items to display.')));
        }
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;

class Annotations extends BaseHtmlElement
{
    use Translation;

    protected iterable $annotations;

    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'annotations'];

    public function __construct(iterable $annotations)
    {
        $this->annotations = $annotations;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Annotations'))));

        $annotations = yield_iterable($this->annotations);
        if ($annotations->valid()) {
            foreach ($annotations as $annotation) {
                $this->addHtml(new HorizontalKeyValue($annotation->name, $annotation->value));
            }
        } else {
            $this->addHtml(new EmptyState($this->translate('No items to display.')));
        }
    }
}

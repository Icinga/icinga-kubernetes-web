<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;

class Annotations extends BaseHtmlElement
{
    use Translation;

    protected $defaultAttributes = ['class' => 'annotations'];

    protected $tag = 'section';

    protected $annotations;

    public function __construct(iterable $annotations)
    {
        $this->annotations = $annotations;
    }

    protected function assemble()
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Annotations'))));

        foreach ($this->annotations as $annotation) {
            $this->addHtml(new HorizontalKeyValue($annotation->name, $annotation->value));
        }
    }
}

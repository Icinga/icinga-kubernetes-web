<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\EmptyState;

class Yaml extends BaseHtmlElement
{
    use Translation;

    protected $yaml;

    protected $tag = 'section';

    public function __construct($yaml)
    {
        $this->yaml = $yaml;
    }

    protected function assemble()
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Yaml'))));

        if (! isset($this->yaml)) {
            $this->addHtml(new EmptyState($this->translate('No data to display.')));

            return;
        }

        $this->addHtml(
            new HtmlElement(
                'div',
                new Attributes([
                    'class'               => 'collapsible',
                    'data-visible-height' => 100
                ]),
                new HtmlElement('pre', null, new Text($this->yaml))
            )
        );
    }
}

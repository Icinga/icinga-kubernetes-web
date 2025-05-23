<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\CopyToClipboard;
use ipl\Web\Widget\EmptyState;

class Yaml extends BaseHtmlElement
{
    use Translation;

    protected ?string $yaml;

    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'yaml'];

    public function __construct(?string $yaml)
    {
        $this->yaml = $yaml;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Yaml'))));

        if (! isset($this->yaml)) {
            $this->addHtml(new EmptyState($this->translate('No data to display.')));

            return;
        }

        $yaml = new HtmlElement('pre', null, new Text($this->yaml));

        CopyToClipboard::attachTo($yaml);

        $this->addHtml(new HtmlElement(
            'div',
            new Attributes([
                'class'               => 'collapsible',
                'data-visible-height' => 100
            ]),
            $yaml
        ));
    }
}

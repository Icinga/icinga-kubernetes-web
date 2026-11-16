<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

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
        if (! isset($this->yaml)) {
            $this->addHtml(new EmptyState($this->translate('No data to display.')));

            return;
        }

        $yaml = new HtmlElement('pre', null, new Text($this->yaml));

        CopyToClipboard::attachTo($yaml);

        $this->addHtml($yaml);
    }
}

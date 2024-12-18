<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\BeforeAssemble;
use Icinga\Module\Kubernetes\Common\FormatString;
use Icinga\Module\Kubernetes\Web\Factory;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Link;

class Dashlet extends BaseHtmlElement
{
    use BeforeAssemble;
    use Translation;

    protected $tag = 'li';

    protected FormatString $summary;

    public function __construct(
        protected string $kind,
        protected string $title,
        string $summary,
        protected ?string $url = null
    ) {
        $this->url = $url !== null ? $url : Factory::createListUrl($kind);
        $this->summary = new FormatString($summary);
    }

    protected function assemble(): void
    {
        $this->addHtml(new Link(
            [
                $this->title,
                Factory::createIcon($this->kind),
                new HtmlElement(
                    'p',
                    null,
                    new Text($this->summary)
                )
            ],
            $this->url
        ));
    }
}

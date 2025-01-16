<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\StateBall;

class DetailState extends BaseHtmlElement
{
    protected string $state;

    protected $tag = 'span';

    public function __construct(string $state)
    {
        $this->state = $state;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            (new HtmlDocument())->addHtml(
                new StateBall($this->state, StateBall::SIZE_MEDIUM),
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'icinga-state-text margin-to-ball']),
                    new Text($this->state)
                )
            )
        );
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason;

use Icinga\Module\Kubernetes\Web\Widget\HighlightDelta;
use Icinga\Module\Kubernetes\Web\Widget\KIcon;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class IcingaStateReasonRow extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'row'];

    public function __construct(
        protected string $state,
        protected string $kind,
        protected string $name,
        protected string $reason,
        protected string $tooltip,
        protected ?string $parentName = null
    ) {
    }

    public function assemble(): void
    {
        $kind = strtolower($this->kind);
        $this->addHtml(
            new StateBall(strtolower($this->state), StateBall::SIZE_MEDIUM),
            ($kind === 'node' ? new Icon('share-nodes') : new KIcon($kind)),
            new HtmlElement(
                'span',
                new Attributes(['class' => 'text']),
                (new HighlightDelta($this->name, $this->parentName ?? ''))
                    ->addAttributes(['class' => 'tooltip-holder', 'title' => $this->tooltip]),
                new HtmlElement('span', new Attributes(['class' => 'reason']), new Text($this->reason))
            )
        );
    }
}

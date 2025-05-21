<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class IcingaStateReason extends BaseHtmlElement
{
    /** @var string[] Patterns to be replaced in plain text plugin output */
    protected const TEXT_PATTERNS = [
        '~\\\n~',
        '~([\[(])OK([])])?~',
        '~([\[(])WARNING([])])?~',
        '~([\[(])CRITICAL([])])?~',
        '~([\[(])UNKNOWN([])])?~',
        '~([\[(])UP([])])?~',
        '~([\[(])DOWN([])])?~',
        '~([\[(])PENDING([])])?~',
        '~\\\\_~',
    ];

    /** @var string[] Replacements for {@see static::TEXT_PATTERNS} */
    protected const TEXT_REPLACEMENTS = [
        "\n",
        '<span class="state-ball ball-size-m state-ok"></span>',
        '<span class="state-ball ball-size-m state-warning"></span>',
        '<span class="state-ball ball-size-m state-critical"></span>',
        '<span class="state-ball ball-size-m state-unknown"></span>',
        '<span class="state-ball ball-size-m state-up"></span>',
        '<span class="state-ball ball-size-m state-down"></span>',
        '<span class="state-ball ball-size-m state-pending"></span>',
        '<i class="icon fa-arrow-turn-up fa level-indicator"></i>',
    ];

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'icinga-state-reason'];

    public function __construct(
        protected string $icingaStateReason,
        protected ?string $icingaState = null
    ) {
    }

    protected function assemble(): void
    {
        [$kind, $message] = explode(' ', $this->icingaStateReason, 2);

        $content = preg_replace(
            self::TEXT_PATTERNS,
            self::TEXT_REPLACEMENTS,
            htmlspecialchars(
                trim($message),
                ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5,
                null,
                false
            )
        );

        // Add zero-width space after commas which are not followed by a whitespace character
        // in oder to help browsers to break words in plugin output.
        $content = preg_replace('/,(?=\S)/', ',&#8203;', $content);

        if ($this->icingaState) {
            $this->addHtml(new StateBall($this->icingaState, StateBall::SIZE_MEDIUM));
        }

        if (strtolower($kind) === 'node') {
            $this->addHtml(new Icon('share-nodes'));
        } else {
            $this->addHtml(new HtmlElement('i', new Attributes(['class' => 'icon kicon-' . strtolower($kind)])));
        }

        $this->addHtml(new HtmlString($content));
    }
}

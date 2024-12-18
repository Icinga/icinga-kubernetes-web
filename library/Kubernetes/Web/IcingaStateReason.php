<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlString;
use ipl\Web\Widget\CopyToClipboard;

class IcingaStateReason extends BaseHtmlElement
{
    /** @var string[] Patterns to be replaced in plain text plugin output */
    protected const TEXT_PATTERNS = [
        '~\\\n~',
        '~([\[(])OK([])]) ?~',
        '~([\[(])WARNING([])]) ?~',
        '~([\[(])CRITICAL([])]) ?~',
        '~([\[(])UNKNOWN([])]) ?~',
        '~([\[(])UP([])]) ?~',
        '~([\[(])DOWN([])]) ?~',
        '~([\[(])PENDING([])]) ?~',
    ];

    /** @var string[] Replacements for {@see static::TEXT_PATTERNS} */
    protected const TEXT_REPLACEMENTS = [
        "\n",
        '<span class="state-ball ball-size-m state-ok"></span> ',
        '<span class="state-ball ball-size-m state-warning"></span> ',
        '<span class="state-ball ball-size-m state-critical"></span> ',
        '<span class="state-ball ball-size-m state-unknown"></span> ',
        '<span class="state-ball ball-size-m state-up"></span> ',
        '<span class="state-ball ball-size-m state-down"></span> ',
        '<span class="state-ball ball-size-m state-pending"></span> ',
    ];

    protected string $icingaStateReason;

    protected $tag = 'pre';

    public function __construct(string $icingaStateReason)
    {
        $this->icingaStateReason = $icingaStateReason;
    }

    protected function assemble(): void
    {
        $content = preg_replace(
            self::TEXT_PATTERNS,
            self::TEXT_REPLACEMENTS,
            htmlspecialchars(
                trim($this->icingaStateReason),
                ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5,
                null,
                false
            )
        );

        // Add zero-width space after commas which are not followed by a whitespace character
        // in oder to help browsers to break words in plugin output.
        $content = preg_replace('/,(?=\S)/', ',&#8203;', $content);

        $this->addHtml(new HtmlString($content));

        CopyToClipboard::attachTo($this);
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason;

use ipl\Html\BaseHtmlElement;

class IcingaStateReason extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'icinga-state-reason'];

    public function __construct(
        protected string $icingaStateReason,
        protected ?string $icingaState = null
    ) {
    }

    protected function assemble(): void
    {
        [$kind, $namespaceSlashName, $message] = explode(' ', $this->icingaStateReason, 3);

        $name = (str_contains($namespaceSlashName, '/') ? explode('/', $namespaceSlashName)[1] : $namespaceSlashName);

        $this->addHtml(new IcingaStateReasonRow(
            $this->icingaState,
            $kind,
            $name,
            $message,
            $this->buildTooltip($kind, $namespaceSlashName)
        ));
    }

    protected function buildTooltip(string $kind, string $name): string
    {
        return sprintf('%s %s', $kind, $name);
    }
}

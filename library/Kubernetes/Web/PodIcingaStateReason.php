<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\HtmlElement;

class PodIcingaStateReason extends IcingaStateReason
{
    protected $uuid;

    protected ?string $parentName;

    public function __construct(
        $uuid,
        string $icingaStateReason,
        string $icingaState,
        ?string $parentName = null
    ) {
        parent::__construct($icingaStateReason, $icingaState);

        $this->uuid = $uuid;
        $this->parentName = $parentName;
    }

    protected function assemble(): void
    {
        $podStateReasonLines = explode("\n", $this->icingaStateReason);
        [$kind, $namespaceSlashName, $reason] = explode(' ', array_shift($podStateReasonLines), 3);

        $this->addHtml(new IcingaStateReasonRow(
            $this->icingaState,
            $kind,
            explode('/', $namespaceSlashName)[1],
            $reason,
            $this->buildTooltip($kind, $namespaceSlashName),
            $this->parentName
        ));

        $containerRows = new HtmlElement('div', new Attributes(['class' => 'container-rows']));

        foreach ($podStateReasonLines as $line) {
            [$state, $kind, $containerName, $reason] = explode(' ', $line, 4);

            $containerRows->addHtml((new IcingaStateReasonRow(
                trim($state, '[]'),
                $kind,
                $containerName,
                $reason,
                $this->buildTooltip($kind, $containerName),
            ))->addAttributes(['class' => 'container-row']));
        }

        $this->addHtml($containerRows);
    }
}

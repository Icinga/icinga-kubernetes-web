<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\Service;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;

use function Icinga\Module\Kubernetes\yield_iterable;

class ServiceIcingaStateReason extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'icinga-state-reason'];

    protected string $state = "ok";

    public function __construct(protected Service $service)
    {
    }

    public function getState(): string
    {
        $this->ensureAssembled();

        return $this->state;
    }

    protected function assemble(): void
    {
        $namespaceSlashName = $this->service->namespace . "/" . $this->service->name;
        $pods = yield_iterable(
            Pod::on(Database::connection())->filter(Filter::equal('service.uuid', $this->service->uuid))
        );

        if (! $pods->valid()) {
            $this->state = "unknown";
            $this->addHtml(new IcingaStateReasonRow(
                "unknown",
                "service",
                $namespaceSlashName,
                "Service is unknown as there are no pods associated.",
                $this->buildTooltip("service", $namespaceSlashName),
                null
            ));

            return;
        }

        $states = [];
        $podRows = new HtmlElement('div', new Attributes(['class' => 'pod-rows']));
        foreach ($pods as $pod) {
            $podRows->addHtml(new PodIcingaStateReason(
                $pod->uuid,
                $pod->icinga_state_reason,
                $pod->icinga_state,
                null
            ));

            $states[] = match ($pod->icinga_state) {
                "ok"       => 0,
                "warning"  => 1,
                "critical" => 2,
                "unknown"  => 3,
            };
        }
        $this->addHtml($podRows);
        $nonZeroCount = count(array_filter($states, fn($v) => $v !== 0));
        $total = count($states);
        if ($nonZeroCount / $total > 0.8) {
            $state = 2;
        } elseif ($nonZeroCount / $total > 0.2) {
            $state = 1;
        } else {
            $state = 0;
        }
        $this->state = match ($state) {
            0 => "ok",
            1 => "warning",
            2 => "critical"
        };
        $this->prependHtml(new IcingaStateReasonRow(
            $this->state,
            "service",
            explode('/', $namespaceSlashName)[1],
            "Service is {$this->state}.",
            $this->buildTooltip("service", $namespaceSlashName),
            null
        ));
    }

    protected function buildTooltip(string $kind, string $name): string
    {
        return sprintf('%s %s', $kind, $name);
    }
}

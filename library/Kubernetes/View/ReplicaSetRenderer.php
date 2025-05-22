<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Web\ItemCountIndicator;
use Icinga\Module\Kubernetes\Web\KIcon;
use Icinga\Module\Kubernetes\Web\WorkloadIcingaStateReason;
use ipl\Html\HtmlDocument;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;

class ReplicaSetRenderer extends BaseResourceRenderer
{
    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(new WorkloadIcingaStateReason($item->uuid, $item->icinga_state_reason, $item->icinga_state));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $pods = (new ItemCountIndicator())
            ->addIndicator('critical', $item->actual_replicas - $item->available_replicas)
            ->addIndicator('pending', $item->desired_replicas - $item->actual_replicas)
            ->addIndicator('ok', $item->available_replicas);

        $footer->addHtml(
            (new HorizontalKeyValue(
                new KIcon('pod'),
                $pods
            ))
                ->addAttributes([
                    'title' => sprintf(
                        $this->translate(
                            '%d %s available (%d unavailable)',
                            '%d:num_of_available_replicas %s:replicas_translation (%d:num_of_unavailable_replicas)'
                        ),
                        $pods->getIndicator('ok'),
                        $this->translatePlural('replica', 'replicas', $pods->getIndicator('ok')),
                        $pods->getIndicator('critical')
                    )
                ]),
            new HorizontalKeyValue(
                new Icon('stopwatch', ['title' => $this->translate('Min Ready Duration')]),
                Format::seconds($item->min_ready_seconds, $this->translate('None'))
            ),
        );
    }
}

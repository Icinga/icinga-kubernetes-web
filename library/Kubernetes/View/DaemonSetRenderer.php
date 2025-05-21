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

class DaemonSetRenderer extends BaseResourceRenderer
{
    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(new WorkloadIcingaStateReason($item->uuid, $item->icinga_state_reason, $item->icinga_state));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $pods = (new ItemCountIndicator())
            ->addIndicator('critical', $item->number_unavailable)
            ->addIndicator('pending', $item->desired_number_scheduled - $item->current_number_scheduled)
            ->addIndicator('ok', $item->number_available);

        $footer->addHtml(
            (new HorizontalKeyValue(
                new KIcon('pod'),
                $pods
            ))
                ->addAttributes([
                    'title' => sprintf(
                        $this->translate(
                            '%d %s available (%d unavailable)',
                            '%d:num_of_available_daemon_pods %s:daemon_pods_translation'
                            . ' (%d:num_of_unavailable_daemon_pods)'
                        ),
                        $pods->getIndicator('ok'),
                        $this->translatePlural('daemon pod', 'daemon pods', $pods->getIndicator('ok')),
                        $pods->getIndicator('critical')
                    )
                ]),
            new HorizontalKeyValue(
                new Icon('stopwatch', ['title' => $this->translate('Min Ready Duration')]),
                Format::seconds($item->min_ready_seconds, $this->translate('None'))
            ),
            new HorizontalKeyValue(
                new Icon('retweet', ['title' => $this->translate('Update Strategy')]),
                $item->update_strategy
            )
        );
    }
}

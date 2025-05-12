<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Web\ItemCountIndicator;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;

class JobRenderer extends BaseResourceRenderer
{
    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $pods = (new ItemCountIndicator())
            ->addIndicator('critical', $item->failed)
            ->addIndicator('pending', $item->active)
            ->addIndicator('ok', $item->succeeded);

        $footer->addHtml(
            (new HorizontalKeyValue(
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
                $pods
            ))
                ->addAttributes([
                    'title' => sprintf(
                        $this->translate(
                            '%d %s available (%d not available)',
                            '%d:num_of_available_pods %s:pods_translation (%d:num_of_unavailable_pods)'
                        ),
                        $pods->getIndicator('ok'),
                        $this->translatePlural('pod', 'pods', $pods->getIndicator('ok')),
                        $pods->getIndicator('critical')
                    )
                ]),
            new HorizontalKeyValue(
                new Icon('grip-lines', ['title' => $this->translate('Parallelism')]),
                $item->parallelism
            ),
            new HorizontalKeyValue(
                new Icon('check-double', ['title' => $this->translate('Completions')]),
                $item->getCompletions()
            ),
            new HorizontalKeyValue(
                new Icon('circle-exclamation', ['title' => $this->translate('Back-off Limit')]),
                $item->backoff_limit
            ),
            new HorizontalKeyValue(
                new Icon('skull-crossbones', ['title' => $this->translate('Active Deadline Duration')]),
                Format::seconds($item->active_deadline_seconds) ?? $this->translate('None')
            ),
            new HorizontalKeyValue(
                new Icon('hourglass-start', ['title' => $this->translate('TTL Duration After Finished')]),
                new Text(Format::seconds($item->ttl_seconds_after_finished) ?? $this->translate('None'))
            )
        );
    }
}

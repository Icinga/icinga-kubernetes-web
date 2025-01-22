<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\DefaultListItemCaption;
use Icinga\Module\Kubernetes\Common\DefaultListItemHeader;
use Icinga\Module\Kubernetes\Common\DefaultListItemMain;
use Icinga\Module\Kubernetes\Common\DefaultListItemTitle;
use Icinga\Module\Kubernetes\Common\DefaultListItemVisual;
use Icinga\Module\Kubernetes\Common\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;

class JobListItem extends BaseListItem
{
    use Translation;
    use DefaultListItemHeader;
    use DefaultListItemCaption;
    use DefaultListItemMain;
    use DefaultListItemTitle;
    use DefaultListItemVisual;

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $pods = (new ItemCountIndicator())
            ->addIndicator('critical', $this->item->failed)
            ->addIndicator('pending', $this->item->active)
            ->addIndicator('ok', $this->item->succeeded);

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
                $this->item->parallelism
            ),
            new HorizontalKeyValue(
                new Icon('check-double', ['title' => $this->translate('Completions')]),
                $this->item->getCompletions()
            ),
            new HorizontalKeyValue(
                new Icon('circle-exclamation', ['title' => $this->translate('Back-off Limit')]),
                $this->item->backoff_limit
            ),
            new HorizontalKeyValue(
                new Icon('skull-crossbones', ['title' => $this->translate('Active Deadline Duration')]),
                Format::seconds($this->item->active_deadline_seconds) ?? $this->translate('None')
            ),
            new HorizontalKeyValue(
                new Icon('hourglass-start', ['title' => $this->translate('TTL Duration After Finished')]),
                new Text(Format::seconds($this->item->ttl_seconds_after_finished) ?? $this->translate('None'))
            )
        );
    }
}

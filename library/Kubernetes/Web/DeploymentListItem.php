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
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;

class DeploymentListItem extends BaseListItem
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
            ->addIndicator('critical', $this->item->unavailable_replicas)
            ->addIndicator('pending', $this->item->desired_replicas - $this->item->actual_replicas)
            ->addIndicator('ok', $this->item->available_replicas);

        $footer->addHtml(
            (new HorizontalKeyValue(
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
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
                Format::seconds($this->item->min_ready_seconds, $this->translate('None'))
            ),
            new HorizontalKeyValue(
                new Icon('retweet', ['title' => $this->translate('Strategy')]),
                $this->item->strategy
            ),
            new HorizontalKeyValue(
                new Icon('skull-crossbones', ['title' => $this->translate('Progress Deadline')]),
                Format::seconds($this->item->progress_deadline_seconds)
            )
        );
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class JobListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml(
            $this->createTitle(),
            new TimeAgo($this->item->created->getTimestamp())
        );
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new Text($this->item->icinga_state_reason));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml(
            $this->createHeader(),
            $this->createCaption(),
            $this->createFooter()
        );
    }

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

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<job> is <icinga_state>'),
            [
                new HtmlElement(
                    'span',
                    new Attributes(['class' => 'namespace-badge']),
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text($this->item->namespace)
                ),
                new Link(
                    (new HtmlDocument())->addHtml(
                        new HtmlElement('i', new Attributes(['class' => 'icon kicon-job'])),
                        new Text($this->item->name)
                    ),
                    Links::job($this->item),
                    new Attributes(['class' => 'subject'])
                )
            ],
            new HtmlElement(
                'span', new Attributes(['class' => 'icinga-state-text']), new Text($this->item->icinga_state)
            )
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->item->icinga_state, StateBall::SIZE_MEDIUM));
    }
}

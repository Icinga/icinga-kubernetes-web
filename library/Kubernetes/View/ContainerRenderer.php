<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use DateTime;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Url;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class ContainerRenderer extends BaseResourceRenderer
{
    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        parent::assembleAttributes($item, $attributes, $layout);

        $attributes->get('class')->addValue('container-list-item');
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $visual->addHtml(new StateBall($item->icinga_state, StateBall::SIZE_SMALL));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $footer->addHtml(
            new HorizontalKeyValue($this->translate('Started'), Icons::ready($item->started)),
            new HorizontalKeyValue($this->translate('Ready'), Icons::ready($item->ready)),
            new HorizontalKeyValue(
                new Icon('arrows-spin', new Attributes(['title' => $this->translate('Restarts')])),
                new Text($item->restart_count)
            ),
            (new HorizontalKeyValue(new Text($this->translate('Image')), new Text($item->image)))
                ->addAttributes([
                    'class' => 'push-left container-image'
                ]),
            new HorizontalKeyValue(
                new Icon('download', new Attributes(['title' => $this->translate('Image Pull Policy')])),
                new Text($item->image_pull_policy)
            )
        );
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<container> is <init-container_state>'),
            new Link(
                (new HtmlDocument())->addHtml(
                    new Icon('box'),
                    new Text($item->name)
                ),
                $this->getDetailUrl($item),
                ['class' => 'subject']
            ),
            new HtmlElement(
                'span',
                new Attributes(['class' => 'icinga-state-text']),
                new Text($item->icinga_state)
            )
        ));
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        $stateDetails = json_decode((string) $item->state_details);

        if (isset($stateDetails->finishedAt)) {
            $time = new DateTime($stateDetails->finishedAt);
        } elseif (isset($stateDetails->startedAt)) {
            $time = new DateTime($stateDetails->startedAt);
        } else {
            $time = null;
        }

        if (isset($time)) {
            $info->addHtml(new TimeAgo($time->getTimestamp()));
        }
    }

    /**
     * Get the detail URL for the item
     *
     * @param $item
     *
     * @return Url
     */
    protected function getDetailUrl($item): Url
    {
        return Links::container($item);
    }
}

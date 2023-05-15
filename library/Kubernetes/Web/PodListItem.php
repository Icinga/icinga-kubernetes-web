<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Web\Usage;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;
use LogicException;

class PodListItem extends BaseListItem
{
    /** @var $item Pod The associated list item */
    /** @var $list PodList The list where the item is part of */

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $content = new Icon($this->getPhaseIcon(), ['class' => ['phase-' . $this->item->phase]]);
//
//        if ($this->item->severity === 'ok' || $this->item->severity === 'err') {
//            $content->setStyle('fa-regular');
//        }

        $visual->addHtml($content);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $content = Html::sprintf(
            t('%s/%s on %s is %s', '<namespace>/<pod> on <node> is <pod_phase>'),
            new Text($this->item->namespace),
            new Link(
                $this->item->name,
                Links::pod($this->item->namespace, $this->item->name),
                ['class' => 'subject']
            ),
            new Link(
                $this->item->node_name,
                Links::node($this->item->node_name),
                ['class' => 'subject']
            ),
            new HtmlElement('span', new Attributes(['class' => 'phase-text']), new Text($this->item->phase))
        );

        $content = Html::sprintf(
            t('%s is %s', '<pod> is <pod_phase>'),
            new Link(
                $this->item->name,
                Links::pod($this->item->namespace, $this->item->name),
                ['class' => 'subject']
            ),
            new HtmlElement('span', new Attributes(['class' => 'phase-text']), new Text($this->item->phase))
        );

        $title->addHtml($content);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $container = new HtmlElement('div', new Attributes(['style' => 'display: inline-flex; align-items: center;']));
        if ($this->item->cpu_requests > 0) {
            $container->addHtml(new Usage($this->item->cpu_requests, $this->item->node->cpu_allocatable));
        }
        if ($this->item->memory_requests > 0) {
            $container->addHtml(new Usage($this->item->memory_requests, $this->item->node->memory_allocatable));
        }
        $container->addHtml(new TimeAgo($this->item->created->getTimestamp()));
        $header->add($container);

//        if ($this->item->recovered_at !== null) {
//            $header->add(Html::tag(
//                'span',
//                ['class' => 'meta'],
//                [
//                    'closed ',
//                    new TimeAgo($this->item->recovered_at->getTimestamp())
//                ]
//            ));
//        } else {
//            $header->add(new TimeSince($this->item->created->getTimestamp()));
//        }
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->add(new VerticalKeyValue('IP', $this->item->ip));
        $keyValue->add(new VerticalKeyValue('QoS', ucfirst(Str::camel($this->item->qos))));
        $containerRestarts = 0;
        $containers = new HtmlElement('span');
        /** @var Container $container */
        foreach ($this->item->container as $container) {
            switch ($container->state) {
                case Container::STATE_RUNNING:
                    $state = 'ok';

                    break;
                case Container::STATE_TERMINATED:
                    $state = 'unknown';

                    break;
                case Container::STATE_WAITING:
                    $state = 'warning';

                    break;
                default:
                    throw new LogicException();
            }
            $containerRestarts += $container->restart_count;
            $containers->addHtml(new StateBall($state, StateBall::SIZE_MEDIUM));
        }
        $keyValue->add(new VerticalKeyValue('Containers', $containers));
        $keyValue->add(new VerticalKeyValue('Restarts', $containerRestarts));
        // TODO(el): Volumes
        $keyValue->add(new VerticalKeyValue('Volumes', new StateBall('ok', StateBall::SIZE_MEDIUM)));
        $keyValue->add(new HtmlElement(
            'div',
            null,
            new HorizontalKeyValue('Namespace', $this->item->namespace),
            new HorizontalKeyValue('Node', $this->item->node_name)
        ));
        $main->add($keyValue);
    }

    protected function getPhaseIcon(): string
    {
        switch ($this->item->phase) {
            case Pod::PHASE_PENDING:
                return Icons::POD_PENDING;
            case Pod::PHASE_RUNNING:
                return Icons::POD_RUNNING;
            case Pod::PHASE_SUCCEEDED:
                return Icons::POD_SUCCEEDED;
            case Pod::PHASE_FAILED:
                return Icons::POD_FAILED;
            default:
                throw new LogicException();
        }
    }
}

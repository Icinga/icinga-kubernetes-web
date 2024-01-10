<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\Pod;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\VerticalKeyValue;

class PodListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header
            ->addHtml($this->createTitle())
            ->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        $keyValue = new HtmlElement('div', new Attributes(['class' => 'key-value']));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('IP'), $this->item->ip));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('QoS'), ucfirst(Str::camel($this->item->qos))));
        $containerRestarts = 0;
        $containers = new HtmlElement('span');
        /** @var Container $container */
        foreach ($this->item->container as $container) {
            switch ($container->state) {
                case Container::STATE_RUNNING:
                    $state = $container->ready ? 'ok' : 'critical';

                    break;
                case Container::STATE_TERMINATED:
                    $state = 'unknown';

                    break;
                case Container::STATE_WAITING:
                    $state = 'pending';

                    break;
                default:
                    $state = 'bug';
            }
            $containerRestarts += $container->restart_count;
            $containers->addHtml(new StateBall($state, StateBall::SIZE_MEDIUM));
        }
        $keyValue->addHtml(new VerticalKeyValue($this->translate('Containers'), $containers));
        $keyValue->addHtml(new VerticalKeyValue($this->translate('Restarts'), $containerRestarts));
        $keyValue->addHtml(new HtmlElement(
            'div',
            null,
            new HorizontalKeyValue($this->translate('Namespace'), $this->item->namespace),
            new HorizontalKeyValue($this->translate('Node'), $this->item->node_name)
        ));
        $main->addHtml($keyValue);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<pod> is <pod_phase>'),
            new Link($this->item->name, Links::pod($this->item), ['class' => 'subject']),
            new HtmlElement('span', null, new Text($this->item->phase))
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $ready = $this->item->condition
            ->filter(Filter::all(Filter::equal('type', 'Ready'), Filter::equal('status', 'True')))
            ->first();

        $visual->addHtml(new Icon(
            $this->getPhaseIcon(),
            [
                'class' => [
                    'pod-phase-' . $this->item->phase,
                    $ready ? 'pod-ready' : 'pod-not-ready'
                ]
            ]
        ));
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
                return Icons::BUG;
        }
    }
}

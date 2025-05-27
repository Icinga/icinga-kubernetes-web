<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Web\Widget\KIcon;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

class NamespaceRenderer extends BaseResourceRenderer
{
    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        if ($item->phase === NamespaceModel::PHASE_ACTIVE) {
            $visual->addHtml(new StateBall('ok', StateBall::SIZE_MEDIUM));
        } else {
            $visual->addHtml(new StateBall('none', StateBall::SIZE_MEDIUM));
        }
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $filter = $this->getResourceFilter($item);
        $resources = $this->getResourcesToCheck();
        $resourceCount = 0;

        foreach ($resources as $resource => $_) {
            $resourceCount += Factory::fetchResource($resource)->filter($filter)->count();
        }

        $caption->addHtml(Html::sprintf(
            $this->translate('Namespace %s has %s resources'),
            $item->name,
            $resourceCount
        ));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $filter = $this->getResourceFilter($item);
        $resources = $this->getResourcesToCheck();
        $db = Database::connection();

        foreach ($resources as $resource => $title) {
            $resourceCount = Factory::fetchResource($resource, $db)->filter($filter)->count();
            $footer->addHtml(new HorizontalKeyValue(
                new HtmlElement('i', new Attributes(['class' => "icon kicon-$resource", 'title' => $title])),
                $resourceCount
            ));
        }
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<namespace> is <namespace_phase>'),
            new Link(
                (new HtmlDocument())->addHtml(
                    new KIcon('namespace'),
                    new Text($item->name)
                ),
                Links::namespace($item),
                new Attributes(['class' => 'subject'])
            ),
            new HtmlElement('span', new Attributes(['class' => 'namespace-phase']), new Text($item->phase))
        ));
    }

    /**
     * Get the filter to use for the resources
     *
     * @return Filter\Condition
     */
    protected function getResourceFilter($item): Filter\Condition
    {
        return Filter::equal('namespace', $item->name);
    }

    /**
     * Get the resources to check for the namespace
     *
     * @return string[]
     */
    protected function getResourcesToCheck(): array
    {
        return [
            'daemonset'             => 'Daemon Sets',
            'deployment'            => 'Deployments',
            'ingress'               => 'Ingresses',
            'persistentvolume'      => 'Persistent Volumes',
            'persistentvolumeclaim' => 'Persistent Volume Claims',
            'pod'                   => 'Pods',
            'replicaset'            => 'Replica Sets',
            'service'               => 'Services',
            'statefulset'           => 'Stateful Sets'
        ];
    }
}

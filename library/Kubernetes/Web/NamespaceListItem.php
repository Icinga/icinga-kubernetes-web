<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

class NamespaceListItem extends BaseListItem
{
    use Translation;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        match ($this->viewMode) {
            ViewModeSwitcher::VIEW_MODE_MINIMAL,
            ViewModeSwitcher::VIEW_MODE_COMMON   =>
            $header->addHtml(
                Html::tag(
                    'span',
                    Attributes::create(['class' => 'header-minimal']),
                    [
                        $this->createTitle(),
                        $this->createCaption()
                    ]
                )
            ),
            ViewModeSwitcher::VIEW_MODE_DETAILED =>
            $header->addHtml($this->createTitle()),
            default                              => null
        };

        $header->addHtml(new TimeAgo($this->item->created->getTimestamp()));
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $filter = $this->getResourceFilter();
        $resources = $this->getResourcesToCheck();
        $resourceCount = 0;

        foreach ($resources as $resource => $_) {
            $resourceCount += Factory::fetchResource($resource)->filter($filter)->count();
        }

        $caption->addHtml(Html::sprintf(
            $this->translate('Namespace %s has %s resources'),
            $this->item->name,
            $resourceCount
        ));
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        if ($this->viewMode === ViewModeSwitcher::VIEW_MODE_DETAILED) {
            $main->addHtml($this->createCaption());
        }

        if ($this->viewMode !== ViewModeSwitcher::VIEW_MODE_MINIMAL) {
            $main->addHtml($this->createFooter());
        }
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $filter = $this->getResourceFilter();
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

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<namespace> is <namespace_phase>'),
            new Link(
                (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                    new Text($this->item->name)
                ),
                Links::namespace($this->item),
                new Attributes(['class' => 'subject'])
            ),
            new HtmlElement('span', new Attributes(['class' => 'namespace-phase']), new Text($this->item->phase))
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        if ($this->item->phase === NamespaceModel::PHASE_ACTIVE) {
            $visual->addHtml(new StateBall('ok', StateBall::SIZE_MEDIUM));
        } else {
            $visual->addHtml(new StateBall('none', StateBall::SIZE_MEDIUM));
        }
    }

    /**
     * Get the filter to use for the resources
     *
     * @return Filter\Condition
     */
    protected function getResourceFilter(): Filter\Condition
    {
        return Filter::equal('namespace', $this->item->name);
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

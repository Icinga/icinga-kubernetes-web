<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Model\StatefulSet;
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
        $resourceCount = DaemonSet::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $resourceCount += Deployment::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $resourceCount += Ingress::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $resourceCount += PersistentVolume::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $resourceCount += PersistentVolumeClaim::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $resourceCount += Pod::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $resourceCount += ReplicaSet::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $resourceCount += Service::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $resourceCount += StateFulSet::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

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
        $resourceCount = DaemonSet::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $footer->addHtml(new HorizontalKeyValue(
            new HtmlElement('i', new Attributes(['class' => 'icon kicon-daemonset', 'title' => 'Daemon Sets'])),
            $resourceCount
        ));

        $resourceCount = Deployment::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $footer->addHtml(new HorizontalKeyValue(
            new HtmlElement('i', new Attributes(['class' => 'icon kicon-deployment', 'title' => 'Deployments'])),
            $resourceCount
        ));

        $resourceCount = Ingress::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $footer->addHtml(new HorizontalKeyValue(
            new HtmlElement('i', new Attributes(['class' => 'icon kicon-ingress', 'title' => 'Ingresses'])),
            $resourceCount
        ));

        $resourceCount = PersistentVolume::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $footer->addHtml(new HorizontalKeyValue(
            new HtmlElement(
                'i',
                new Attributes(['class' => 'icon kicon-persistentvolume', 'title' => 'Persistent Volumes'])
            ),
            $resourceCount
        ));

        $resourceCount = PersistentVolumeClaim::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $footer->addHtml(new HorizontalKeyValue(
            new HtmlElement(
                'i',
                new Attributes(['class' => 'icon kicon-persistentvolumeclaim', 'title' => 'Persistent Volume Claims'])
            ),
            $resourceCount
        ));

        $resourceCount = Pod::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $footer->addHtml(new HorizontalKeyValue(
            new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod', 'title' => 'Pods'])),
            $resourceCount
        ));

        $resourceCount = ReplicaSet::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $footer->addHtml(new HorizontalKeyValue(
            new HtmlElement('i', new Attributes(['class' => 'icon kicon-replicaset', 'title' => 'Replica Sets'])),
            $resourceCount
        ));

        $resourceCount = Service::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $footer->addHtml(new HorizontalKeyValue(
            new HtmlElement('i', new Attributes(['class' => 'icon kicon-service', 'title' => 'Services'])),
            $resourceCount
        ));

        $resourceCount = StateFulSet::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->item->name))->count();

        $footer->addHtml(new HorizontalKeyValue(
            new HtmlElement('i', new Attributes(['class' => 'icon kicon-statefulset', 'title' => 'Stateful Sets'])),
            $resourceCount
        ));
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
}

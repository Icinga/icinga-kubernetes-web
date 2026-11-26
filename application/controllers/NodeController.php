<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\TBD\ObjectSuggestions;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Controller\EventsTab;
use Icinga\Module\Kubernetes\Web\Controls\QuickActions;
use Icinga\Module\Kubernetes\Web\Controls\ViewModeSwitcher;
use Icinga\Module\Kubernetes\Web\Detail\NodeDetail;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\Widget\Yaml;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;
use Ramsey\Uuid\Uuid;

class NodeController extends Controller
{
    use EventsTab;

    protected Node $node;

    public function init(): void
    {
        $this->assertPermission(Auth::SHOW_NODES);

        $this->createResourceTabs($this->translate('Node'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $node = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_NODES, Node::on(Database::connection()))
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        $favorite = Favorite::on(Database::connection())
            ->filter(
                Filter::all(
                    Filter::equal('resource_uuid', $uuidBytes),
                    Filter::equal('username', Auth::getInstance()->getUser()->getUsername())
                )
            )
            ->first();

        if ($node === null) {
            $this->httpNotFound($this->translate('Node not found'));
        }

        $this->addControl(
            (new ResourceList([$node]))
                ->setDetailActionsDisabled()
                ->setViewMode(ViewMode::Minimal)
        );

        $this->addControl(new QuickActions($node, $favorite));

        $this->node = $node;
    }

    public function indexAction(): void
    {
        $this->addContent(new NodeDetail($this->node));
    }

    public function podsAction(): void
    {
        $this->assertPermission(Auth::SHOW_PODS);

        // Shift the ID so that the search bar doesn't parse it as a filter.
        $this->params->shift('id');

        $pods = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_PODS, Pod::on(Database::connection()))
            ->filter(Filter::all(
                Filter::equal('cluster_uuid', $this->node->cluster_uuid),
                Filter::equal('node_name', $this->node->name)
            ));

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl($pods, [
            'pod.created desc' => $this->translate('Created'),
            'pod.name'         => $this->translate('Name'),
            'pod.namespace'    => $this->translate('Namespace'),
            'pod.phase'        => $this->translate('Phase')
        ]);
        $paginationControl = $this->createPaginationControl($pods);
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $preserveParams = [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            'id'
        ];
        $requestParams = Url::fromRequest()->onlyWith($preserveParams)->getParams();

        $searchBar = $this->createSearchBar($pods, $preserveParams)
            ->setEditorUrl(Url::fromPath('kubernetes/node/pods-search-editor')->setParams($requestParams))
            ->setSuggestionUrl(Url::fromPath('kubernetes/node/pods-complete')->setParams(clone $requestParams));
        // Not handled by createSearchBar(), see EventsTab::callHandleRequest()
        $searchBar->handleRequest(ServerRequest::fromGlobals());

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();

                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $pods->filter($filter);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $this->addContent((new ResourceList($pods))->setViewMode($viewModeSwitcher->getViewMode()));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function podsCompleteAction(): void
    {
        $this->assertPermission(Auth::SHOW_PODS);

        $this->getDocument()->addHtml(
            (new ObjectSuggestions())
                ->setModel(Pod::class)
                ->forRequest($this->getServerRequest())
        );
    }

    public function podsSearchEditorAction(): void
    {
        $this->assertPermission(Auth::SHOW_PODS);

        $preserveParams = [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
            'id'
        ];

        $editor = $this->createSearchEditor(
            Pod::on(Database::connection()),
            Url::fromPath('kubernetes/node/pods'),
            $preserveParams
        );
        $editor->setSuggestionUrl(
            Url::fromPath('kubernetes/node/pods-complete')
                ->setParams(Url::fromRequest()->onlyWith($preserveParams)->getParams())
        );
        // Not handled by createSearchEditor(), see EventsTab::callHandleRequest()
        $editor->handleRequest(ServerRequest::fromGlobals());

        $this->getDocument()->addHtml($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }

    protected function getIgnoredViewModes(): array
    {
        // The pods list supports all view modes, the events list (see EventsTab) doesn't.
        return str_starts_with($this->getRequest()->getActionName(), 'pods') ? [] : [ViewMode::Detailed];
    }

    public function yamlAction(): void
    {
        $this->assertPermission(Auth::SHOW_YAML);

        $this->addContent(new Yaml($this->node->yaml));
    }
}

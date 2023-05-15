<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\TBD\ObjectSuggestions;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\NodeList;
use ipl\Web\Compat\SearchControls;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class NodesController extends Controller
{
    use SearchControls;

    public function indexAction(): void {
        $this->addTitleTab($this->translate('Nodes'));

        $nodes = Node::on(Database::connection());

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl(
            $nodes,
            [
                'node.name'    => $this->translate('Name'),
                'node.created' => $this->translate('Created')
            ]
        );

        $paginationControl = $this->createPaginationControl($nodes);
        $searchBar = $this->createSearchBar($nodes, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
        ]);

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

        $nodes->filter($filter);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $this->addContent(new NodeList($nodes));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function completeAction(): void
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Pod::class);
        $suggestions->forRequest($this->getServerRequest());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor(Pod::on(Database::connection()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }

    protected function getPageSize($default)
    {
        return parent::getPageSize($default ?? 50);
    }
}

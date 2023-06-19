<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\TBD\ObjectSuggestions;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\PersistentVolumeClaimList;
use Icinga\Module\Kubernetes\Web\PodList;
use ipl\Web\Compat\SearchControls;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class PersistentvolumeclaimsController extends Controller
{
    use SearchControls;

    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Persistent Volume Claims'));

        $pvcs = PersistentVolumeClaim::on(Database::connection());

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl(
            $pvcs,
            [
                'pvc.name'    => $this->translate('Name'),
                'pvc.created' => $this->translate('Created')
            ]
        );

        $paginationControl = $this->createPaginationControl($pvcs);
        $searchBar = $this->createSearchBar($pvcs, [
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

        $pvcs->filter($filter);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $this->addContent(new PersistentVolumeClaimList($pvcs));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function completeAction(): void
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(PersistentVolumeClaim::class);
        $suggestions->forRequest($this->getServerRequest());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor(PersistentVolumeClaim::on(Database::connection()), [
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

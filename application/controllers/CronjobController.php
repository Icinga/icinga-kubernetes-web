<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Kubernetes\Web\Controls\QuickActions;
use Icinga\Module\Kubernetes\Web\Detail\CronJobDetail;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class CronjobController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission(Auth::SHOW_CRON_JOBS);

        $this->addTitleTab($this->translate('Cron Job'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        $cronJob = Auth::getInstance()
            ->withRestrictions(Auth::SHOW_CRON_JOBS, CronJob::on(Database::connection()))
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

        if ($cronJob === null) {
            $this->httpNotFound($this->translate('Cron Job not found'));
        }

        $this->addControl(
            (new ResourceList([$cronJob]))
                ->setDetailActionsDisabled()
                ->setViewMode(ViewMode::Minimal)
        );

        $this->addControl(new QuickActions($cronJob, $favorite));

        $this->addContent(new CronJobDetail($cronJob));
    }
}

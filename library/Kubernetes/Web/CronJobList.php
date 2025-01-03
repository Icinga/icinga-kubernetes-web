<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;
use Icinga\Module\Kubernetes\Common\ViewMode;

class CronJobList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'cronjob-list'];

    protected function getItemClass(): string
    {
        return match ($this->getViewMode()) {
            'minimal'  => CronJobListItemMinimal::class,
            'detailed' => CronJobListItemDetailed::class,
            default    => CronJobListItem::class,
        };
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\BaseItemList;

class CronJobList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'cron-job-list'];

    protected function getItemClass(): string
    {
        return CronJobListItem::class;
    }
}

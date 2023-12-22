<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Secret;
use Icinga\Module\Kubernetes\Web\ListController;
use Icinga\Module\Kubernetes\Web\SecretList;
use ipl\Orm\Query;

class SecretsController extends ListController
{
    protected function getContentClass(): string
    {
        return SecretList::class;
    }

    protected function getQuery(): Query
    {
        return Secret::on(Database::connection());
    }

    protected function getSortColumns(): array
    {
        return [
            'secret.created desc' => $this->translate('Created'),
            'secret.name'         => $this->translate('Name'),
            'secret.namespace'    => $this->translate('Namespace')
        ];
    }

    protected function getTitle(): string
    {
        return $this->translate('Secrets');
    }
}

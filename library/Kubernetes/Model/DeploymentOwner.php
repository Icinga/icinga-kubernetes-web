<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class DeploymentOwner extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'deployment_uuid',
            'owner_uuid'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('deployment', Deployment::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'kind'                  => $this->translate('Kind'),
            'name'                  => $this->translate('Name'),
            'uid'                   => $this->translate('UID'),
            'controller'            => $this->translate('Controller'),
            'block_owner_deletion'  => $this->translate('Block Owner Deletion'),
        ];
    }

    public function getColumns(): array
    {
        return [
            'deployment_uuid',
            'owner_uuid',
            'kind',
            'name',
            'uid',
            'controller',
            'block_owner_deletion'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['deployment_owner.name asc'];
    }

    public function getKeyName(): array
    {
        return ['deployment_uuid', 'owner_uuid'];
    }

    public function getTableName(): string
    {
        return 'deployment_owner';
    }
}

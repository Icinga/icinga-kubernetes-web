<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class DeploymentCondition extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'deployment_uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_update',
            'last_transition'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('deployment', Deployment::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'type'            => $this->translate('Type'),
            'status'          => $this->translate('Status'),
            'last_update'     => $this->translate('Last Update'),
            'last_transition' => $this->translate('Last Transition'),
            'message'         => $this->translate('Message'),
            'reason'          => $this->translate('Reason')
        ];
    }

    public function getColumns(): array
    {
        return [
            'status',
            'last_update',
            'last_transition',
            'message',
            'reason'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['last_transition desc'];
    }

    public function getKeyName(): array
    {
        return ['deployment_uuid', 'type'];
    }

    public function getTableName(): string
    {
        return 'deployment_condition';
    }
}

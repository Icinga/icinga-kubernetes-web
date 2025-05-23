<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ServiceCondition extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'service_uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_transition'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('service', Service::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'type'                => $this->translate('Type'),
            'status'              => $this->translate('Status'),
            'observed_generation' => $this->translate('Observed Generation'),
            'last_transition'     => $this->translate('Last Transition'),
            'reason'              => $this->translate('Reason'),
            'message'             => $this->translate('Message')
        ];
    }

    public function getColumns(): array
    {
        return [
            'status',
            'observed_generation',
            'last_transition',
            'reason',
            'message'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['service_condition.last_transition desc'];
    }

    public function getKeyName(): array
    {
        return ['service_uuid', 'type'];
    }

    public function getTableName(): string
    {
        return 'service_condition';
    }
}

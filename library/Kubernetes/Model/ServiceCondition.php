<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'service_uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_transition'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('service', Service::class);
    }

    public function getColumnDefinitions()
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

    public function getColumns()
    {
        return [
            'status',
            'observed_generation',
            'last_transition',
            'reason',
            'message'
        ];
    }

    public function getDefaultSort()
    {
        return ['last_transition desc'];
    }

    public function getKeyName()
    {
        return ['service_uuid', 'type'];
    }

    public function getTableName()
    {
        return 'service_condition';
    }
}

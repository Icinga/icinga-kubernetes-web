<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class StatefulSetCondition extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'stateful_set_uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_transition'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('stateful_set', StatefulSet::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'type'            => $this->translate('Type'),
            'status'          => $this->translate('Status'),
            'last_transition' => $this->translate('Last Transition'),
            'message'         => $this->translate('Message'),
            'reason'          => $this->translate('Reason')
        ];
    }

    public function getColumns()
    {
        return [
            'status',
            'last_transition',
            'message',
            'reason'
        ];
    }

    public function getDefaultSort()
    {
        return ['last_transition desc'];
    }

    public function getKeyName()
    {
        return ['stateful_set_uuid', 'type'];
    }

    public function getTableName()
    {
        return 'stateful_set_condition';
    }
}

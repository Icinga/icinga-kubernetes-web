<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class NodeCondition extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'node_id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_heartbeat',
            'last_transition'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('node', Node::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'type'            => $this->translate('Type'),
            'status'          => $this->translate('Status'),
            'last_heartbeat'  => $this->translate('Last Heartbeat'),
            'last_transition' => $this->translate('Last Transition'),
            'message'         => $this->translate('Message'),
            'reason'          => $this->translate('Reason')
        ];
    }

    public function getColumns()
    {
        return [
            'status',
            'last_heartbeat',
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
        return ['node_id', 'type'];
    }

    public function getTableName()
    {
        return 'node_condition';
    }
}

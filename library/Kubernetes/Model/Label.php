<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Label extends Model
{
    public function getTableName()
    {
        return 'label';
    }

    public function getKeyName()
    {
        return ['id'];
    }

    public function getColumns()
    {
        return [
            'name',
            'value'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'name'            => t('Name'),
            'value'          => t('Value')
        ];
    }

    public function getDefaultSort()
    {
        return ['name'];
    }
//
//    public function getSearchColumns()
//    {
//        return ['severity'];
//    }
//
//    public function getDefaultSort()
//    {
//        return ['last_transition desc'];
//    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_label');
//
//        $relations->belongsToMany('contact', Contact::class)
//            ->through('incident_contact');
//
//        $relations->hasMany('incident_contact', IncidentContact::class);
//        $relations->hasMany('incident_history', IncidentHistory::class);
    }
}

<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IngressTls extends Model
{
    public function getTableName()
    {
        return 'ingress_tls';
    }

    public function getKeyName()
    {
        return 'ingress_id';
    }

    public function getColumns()
    {
        return [
            'tls_host',
            'tls_secret'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'tls_host'   => t('TLS Host'),
            'tls_secret' => t('TLS Secret')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'ingress_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('ingress', Ingress::class);
    }
}

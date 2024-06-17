<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IngressTls extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'ingress_uuid'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('ingress', Ingress::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'tls_host'   => $this->translate('TLS Host'),
            'tls_secret' => $this->translate('TLS Secret')
        ];
    }

    public function getColumns()
    {
        return [
            'tls_host',
            'tls_secret'
        ];
    }

    public function getKeyName()
    {
        return 'ingress_uuid';
    }

    public function getTableName()
    {
        return 'ingress_tls';
    }
}

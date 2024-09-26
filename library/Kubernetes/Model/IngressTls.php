<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IngressTls extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'ingress_uuid'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('ingress', Ingress::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'tls_host'   => $this->translate('TLS Host'),
            'tls_secret' => $this->translate('TLS Secret')
        ];
    }

    public function getColumns(): array
    {
        return [
            'tls_host',
            'tls_secret'
        ];
    }

    public function getKeyName(): string
    {
        return 'ingress_uuid';
    }

    public function getTableName(): string
    {
        return 'ingress_tls';
    }
}

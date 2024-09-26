<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class PodPvc extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'pod_uuid'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('pod', Pod::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'volume_name' => $this->translate('Volume Name'),
            'claim_name'  => $this->translate('Claim Name'),
            'read_only'   => $this->translate('Readonly')
        ];
    }

    public function getColumns(): array
    {
        return [
            'read_only'
        ];
    }

    public function getKeyName(): array
    {
        return ['pod_uuid', 'volume_name', 'claim_name'];
    }

    public function getTableName(): string
    {
        return 'pod_pvc';
    }
}

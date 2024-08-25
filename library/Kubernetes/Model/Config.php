<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Model;

/**
 * @property string $key Arbitrary unique config key
 * @property string $value The actual config value
 */
class Config extends Model
{
    public function getTableName(): string
    {
        return 'config';
    }

    public function getKeyName(): string
    {
        return 'key';
    }

    public function getColumns(): array
    {
        return [
            'key',
            'value'
        ];
    }
}

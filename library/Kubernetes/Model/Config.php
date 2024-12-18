<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

/**
 * @property string $key Arbitrary unique config key
 * @property string $value The actual config value
 * @property bool $locked Whether config is locked, i.e. managed via daemon YAML
 */
class Config extends Model
{
    public const DEFAULT_NOTIFICATIONS_NAME = 'Icinga for Kubernetes';

    public const DEFAULT_NOTIFICATIONS_TYPE = 'kubernetes';

    public const NOTIFICATIONS_URL = 'notifications.url';

    public const NOTIFICATIONS_USERNAME = 'notifications.username';

    public const NOTIFICATIONS_PASSWORD = 'notifications.password';

    public const NOTIFICATIONS_KUBERNETES_WEB_URL = 'notifications.kubernetes_web_url';

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
            'value',
            'locked'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new BoolCast([
            'locked'
        ]));
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\Orm\Behavior\Binary;
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

    public const PROMETHEUS_URL = 'prometheus.url';

    public const PROMETHEUS_INSECURE = 'prometheus.insecure';

    public const PROMETHEUS_USERNAME = 'prometheus.username';

    public const PROMETHEUS_PASSWORD = 'prometheus.password';

    public static function transformKeyForForm(string $key): string
    {
        return strtr($key, ['notifications.' => 'notifications_', 'prometheus.' => 'prometheus_']);
    }

    public static function transformKeyForDb(string $key): string
    {
        return strtr($key, ['notifications_' => 'notifications.', 'prometheus_' => 'prometheus.']);
    }

    public function getTableName(): string
    {
        return 'config';
    }

    public function getKeyName(): array
    {
        return ['cluster_uuid', 'key'];
    }

    public function getColumns(): array
    {
        return [
            'cluster_uuid',
            'key',
            'value',
            'locked'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'cluster_uuid'
        ]));

        $behaviors->add(new BoolCast([
            'locked'
        ]));
    }
}

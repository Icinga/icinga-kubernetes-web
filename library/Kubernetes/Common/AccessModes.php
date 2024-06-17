<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

class AccessModes
{
    public const READ_ONLY_MANY = 1 << 1;

    public const READ_WRITE_MANY = 1 << 2;

    public const READ_WRITE_ONCE = 1 << 0;

    public const READ_WRITE_ONCE_POD = 1 << 3;

    public static $names = [
        self::READ_WRITE_ONCE     => 'ReadWriteOnce',
        self::READ_ONLY_MANY      => 'ReadOnlyMany',
        self::READ_WRITE_MANY     => 'ReadWriteMany',
        self::READ_WRITE_ONCE_POD => 'ReadWriteOncePod'
    ];

    public static function asNames(int $bitmask): array
    {
        $names = [];

        foreach (static::$names as $flag => $name) {
            if ($bitmask & $flag) {
                $names[] = $name;
            }
        }

        return $names;
    }
}

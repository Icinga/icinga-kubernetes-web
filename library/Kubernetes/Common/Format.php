<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

abstract class Format
{
    public const TIME_SECOND = 1;

    public const TIME_MINUTE = 60 * self::TIME_SECOND;

    public const TIME_HOUR = 60 * self::TIME_MINUTE;

    public const TIME_DAY = 24 * self::TIME_HOUR;

    public const TIME_YEAR = 365 * self::TIME_DAY;

    protected const TIME_UNITS = [
        "year"   => self::TIME_YEAR,
        "day"    => self::TIME_DAY,
        "hour"   => self::TIME_HOUR,
        "minute" => self::TIME_MINUTE,
        "second" => self::TIME_SECOND
    ];

    public static function seconds(?int $seconds, ?string $zeroAs = null): ?string
    {
        if ($seconds === null) {
            return null;
        }

        if ($seconds === 0) {
            return $zeroAs;
        }

        $parts = [];

        foreach (static::TIME_UNITS as $unitName => $unitSeconds) {
            $unitValue = intdiv($seconds, $unitSeconds);
            $seconds %= $unitSeconds;
            if ($unitValue > 0) {
                $parts[] = $unitValue . " " . $unitName . ($unitValue > 1 ? "s" : "");
            }
        }

        return match (true) {
            count($parts) === 1 => $parts[0],
            count($parts) === 2 => implode(" and ", $parts),
            default             => implode(", ", array_slice($parts, 0, -1)) . " and " . end($parts),
        };
    }
}

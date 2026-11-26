<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

/**
 * Supported Notifications URL formats
 */
class NotificationsUrl
{
    /**
     * Check whether a URL uses HTTP or HTTPS and contains a host
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isWeb(string $url): bool
    {
        $parts = parse_url($url);

        return $parts !== false
            && isset($parts['scheme'], $parts['host'])
            && in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            && ! preg_match('/[\x00-\x1f\x7f]/', $url)
            && ! preg_match('/[\x00-\x20\x7f]/', $parts['host']);
    }

    /**
     * Check whether a Notifications URL is supported, including an empty value to disable notifications
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isValid(string $url): bool
    {
        return $url === '' || static::isWeb($url);
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

/**
 * Supported Notifications URL formats
 */
class NotificationsUrl
{
    /** @var int Maximum length of a Unix URL in the Kubernetes configuration table */
    public const MAX_UNIX_URL_LENGTH = 255;

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

        if (
            $parts === false
            || ! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || preg_match('/[\x00-\x1f\x7f]/', $url)
            || preg_match('/[\x00-\x20\x7f]/', $parts['host'])
        ) {
            return false;
        }

        // Go decodes these components when loading the URL and leaves the query opaque.
        foreach (['user', 'pass', 'host', 'path', 'fragment'] as $component) {
            if (preg_match('/%(?![a-fA-F0-9]{2})/', $parts[$component] ?? '')) {
                return false;
            }
        }

        $host = $parts['host'];

        return ! str_contains($host, '[') || (
            preg_match('/^\[([^\[\]%]+)(?:%25[^\[\]]+)?\]$/D', $host, $matches)
            && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
        );
    }

    /**
     * Check whether a URL specifies an absolute Unix socket path without an authority, query, or fragment
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isUnix(string $url): bool
    {
        if (! str_starts_with($url, 'unix:///')) {
            return false;
        }

        $path = substr($url, 7);

        return strlen($path) > 1
            && ! str_starts_with($path, '//')
            && ! preg_match('/[?\x23\x00-\x20\x7f]/', $path)
            && ! preg_match('/%(?![a-fA-F0-9]{2})/', $path)
            && ! str_contains(rawurldecode($path), "\0");
    }

    /**
     * Get a Unix URL with filesystem path characters escaped
     *
     * @param string $path Absolute filesystem path
     *
     * @return string
     */
    public static function fromSocketPath(string $path): string
    {
        return 'unix://' . str_replace('%2F', '/', rawurlencode($path));
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
        return $url === '' || static::isWeb($url)
            || (strlen($url) <= static::MAX_UNIX_URL_LENGTH && static::isUnix($url));
    }
}

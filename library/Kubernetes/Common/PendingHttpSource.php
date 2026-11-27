<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

/**
 * Cluster settings expected when activating a newly provisioned HTTP source
 */
final readonly class PendingHttpSource
{
    /**
     * Create a new PendingHttpSource
     *
     * @param string $storedUsername Username during staging, empty when the username is unlocked
     * @param bool $usernameLocked Whether the username is managed in the daemon configuration
     * @param string $password The newly generated password
     * @param string $storedUrl URL stored during staging, or an empty string when the URL is absent
     */
    public function __construct(
        public string $storedUsername,
        public bool $usernameLocked,
        public string $password,
        public string $storedUrl,
    ) {
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Application\Modules\Module;
use Icinga\Module\Kubernetes\Model\Config;
use Icinga\Module\Notifications\Integrations\V1\Source;
use RuntimeException;

/**
 * A Notifications V1 source handle for provisioning and confirmed password rotation
 */
class NotificationsSource
{
    /**
     * Create a new NotificationsSource
     *
     * @param Source $source Notifications handle for the selected username
     */
    final public function __construct(protected readonly Source $source)
    {
    }

    /**
     * Load the handle for a listener username
     *
     * @param string $username
     *
     * @return static
     */
    public static function get(string $username): static
    {
        return new static(Source::get($username));
    }

    /**
     * Check whether the enabled Notifications module provides the public source API
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return Module::exists('notifications') && class_exists(Source::class);
    }

    /**
     * Get the existing source name
     *
     * @return ?string Null if the username was vacant when the handle was loaded
     */
    public function getName(): ?string
    {
        return $this->source->getName();
    }

    /**
     * Check whether the source has the Kubernetes type
     *
     * @return ?bool Null if the installed Notifications V1 API cannot report the source type
     */
    public function isKubernetes(): ?bool
    {
        return self::isKubernetesSource($this->source);
    }

    /**
     * Create a Kubernetes source if the handle was loaded for a vacant username
     *
     * @param string $name
     * @param ?string $password Password for HTTP, or null for Unix sockets
     *
     * @return void
     *
     * @throws RuntimeException If this handle represents an existing source
     */
    public function create(string $name, ?string $password = null): void
    {
        if ($this->getName() !== null) {
            throw new RuntimeException('The Notifications source already exists.');
        }

        $this->source->setName($name)->setType(Config::DEFAULT_NOTIFICATIONS_TYPE);
        if ($password !== null) {
            $this->source->setPassword($password);
        }

        $this->source->save();
    }

    /**
     * Apply a new password to the dedicated existing source verified by the administrator
     *
     * @param string $password Locally configured password
     *
     * @return void
     */
    public function rotatePassword(string $password): void
    {
        $this->source->setPassword($password)->save();
    }

    /**
     * Check for the source type getter at runtime across Notifications V1 versions
     *
     * @param object $source A source from the installed Notifications V1 API
     *
     * @return ?bool Null when the getter is unavailable
     */
    private static function isKubernetesSource(object $source): ?bool
    {
        return method_exists($source, 'getType') ? $source->getType() === Config::DEFAULT_NOTIFICATIONS_TYPE : null;
    }
}

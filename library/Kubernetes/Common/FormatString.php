<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Stringable;

/**
 * Format strings with support for both named placeholders and sprintf-style arguments.
 */
class FormatString implements Stringable
{
    protected array $args = [];

    /**
     * Constructor for the FormatString class.
     *
     * @param string $format The format string containing named placeholders and/or sprintf-style arguments.
     */
    public function __construct(protected string $format)
    {
    }

    /**
     * Add arguments for formatting the string.
     *
     * @param array $args An associative array for named placeholders and/or an indexed array for sprintf arguments.
     *
     * @return static Returns the instance of the FormatString for method chaining.
     */
    public function addArgs(array $args): static
    {
        $this->args = array_merge($this->args, $args);

        return $this;
    }

    /**
     * Convert the format string to a formatted string using the provided arguments.
     *
     * @return string The formatted string.
     */
    public function __toString(): string
    {
        return ! empty($this->args) ? vsprintf(strtr($this->format, $this->args), $this->args) : $this->format;
    }
}

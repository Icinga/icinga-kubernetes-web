<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

use ipl\Web\Url;

abstract class Links
{
    public static function node(string $name): Url
    {
        return Url::fromPath('kubernetes/node', ['name' => $name]);
    }

    public static function pod(string $namespace, string $name): Url
    {
        return Url::fromPath('kubernetes/pod', ['namespace' => $namespace, 'name' => $name]);
    }
}

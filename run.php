<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

/** @var Module $this */

use Icinga\Application\Modules\Module;

if (! function_exists('yield_iterable')) {
    require_once __DIR__ . '/library/Kubernetes/functions.php';
}

$this->provideHook('Health');

if (Module::exists('notifications')) {
    $this->provideHook('Notifications/ObjectsRenderer');
}

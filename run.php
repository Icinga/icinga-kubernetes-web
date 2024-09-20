<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

if (! function_exists('yield_iterable')) {
    require_once __DIR__ . '/library/Kubernetes/functions.php';
}

if (\Icinga\Application\Icinga::app()->getModuleManager()->hasEnabled('icingadb')) {
    $this->provideHook('Health');
}

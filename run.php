<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

if (! function_exists('yield_iterable')) {
    /**
     * Turn any iterable into a generator
     *
     * @param iterable $iterable
     *
     * @return Generator
     */
    function yield_iterable(iterable $iterable): Generator
    {
        foreach ($iterable as $k => $v) {
            yield $k => $v;
        }
    }
}

/** @var $this \Icinga\Application\Modules\Module */

if ($this::exists('notifications')) {
    $this->provideHook('ApplicationState', 'AutoPopulateSource');
}

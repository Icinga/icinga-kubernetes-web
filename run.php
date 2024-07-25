<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

/** @var $this \Icinga\Application\Modules\Module */

if ($this::exists('notifications')) {
    $this->provideHook('ApplicationState', 'AutoPopulateSource');
}

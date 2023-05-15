<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

/** @var \Icinga\Application\Modules\Module $this */

$section = $this->menuSection(
    'Kubernetes',
    [
        'icon' => 'globe',
    ]
);

$section->add(
    N_('Pods'),
    [
        'description'   => $this->translate('Pods'),
        'url'           => 'kubernetes/pods'
    ]
);

$this->provideConfigTab(
    'database',
    [
        'title' => $this->translate('Database'),
        'label' => $this->translate('Database'),
        'url'   => 'config/database'
    ]
);

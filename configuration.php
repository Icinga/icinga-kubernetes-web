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

$section->add(
    N_('Deployments'),
    [
        'description'   => $this->translate('Deployments'),
        'url'           => 'kubernetes/deployments'
    ]
);

$section->add(
    N_('Stateful Sets'),
    [
        'description'   => $this->translate('Stateful Sets'),
        'url'           => 'kubernetes/statefulsets'
    ]
);

$section->add(
    N_('ReplicaSets'),
    [
        'description'   => $this->translate('ReplicaSets'),
        'url'           => 'kubernetes/replicasets'
    ]
);

$section->add(
    N_('Daemon Sets'),
    [
        'description'   => $this->translate('Daemon Sets'),
        'url'           => 'kubernetes/daemonsets'
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

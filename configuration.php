<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

/** @var \Icinga\Application\Modules\Module $this */

$section = $this->menuSection(
    'Kubernetes',
    [
        'icon' => 'globe',
    ]
);

$i = 0;

/*$section->add(
    N_('Dashboard'),
    [
        'description' => $this->translate('Dashboard'),
        'url'         => 'kubernetes/dashboard',
        'priority'    => $i++
    ]
);*/

$section->add(
    N_('Nodes'),
    [
        'description' => $this->translate('Nodes'),
        'url'         => 'kubernetes/nodes',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Namespaces'),
    [
        'description'   => $this->translate('Namespaces'),
        'url'           => 'kubernetes/namespaces'
    ]
);

$section->add(
    N_('Deployments'),
    [
        'description' => $this->translate('Deployments'),
        'url'         => 'kubernetes/deployments',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Replica Sets'),
    [
        'description' => $this->translate('Replica Sets'),
        'url'         => 'kubernetes/replicasets',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Daemon Sets'),
    [
        'description' => $this->translate('Daemon Sets'),
        'url'         => 'kubernetes/daemonsets',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Stateful Sets'),
    [
        'description' => $this->translate('Stateful Sets'),
        'url'         => 'kubernetes/statefulsets',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Events'),
    [
        'description' => $this->translate('Events'),
        'url'         => 'kubernetes/events',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Pods'),
    [
        'description' => $this->translate('Pods'),
        'url'         => 'kubernetes/pods',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Persistent Volume Claims'),
    [
        'description' => $this->translate('Persistent Volume Claims'),
        'url'         => 'kubernetes/persistentvolumeclaims',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Persistent Volumes'),
    [
        'description' => $this->translate('Persistent Volumes'),
        'url'         => 'kubernetes/persistentvolumes',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Secrets'),
    [
        'description' => $this->translate('Secrets'),
        'url'         => 'kubernetes/secrets',
        'priority'    => $i++
    ]
);

$section->add(
    N_('Config Maps'),
    [
        'description' => $this->translate('Config Maps'),
        'url'         => 'kubernetes/configmaps',
        'priority'    => $i++
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

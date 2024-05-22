<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

/** @var Module $this */

use Icinga\Application\Modules\Module;

$section = $this->menuSection(
    'Kubernetes',
    [
        'icon' => 'globe'
    ]
);

$priority = 0;

$section->add(
    N_('Nodes'),
    [
        'description' => $this->translate('Nodes'),
        'url'         => 'kubernetes/nodes',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Namespaces'),
    [
        'description' => $this->translate('Namespaces'),
        'url'         => 'kubernetes/namespaces',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Deployments'),
    [
        'description' => $this->translate('Deployments'),
        'url'         => 'kubernetes/deployments',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Replica Sets'),
    [
        'description' => $this->translate('Replica Sets'),
        'url'         => 'kubernetes/replicasets',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Daemon Sets'),
    [
        'description' => $this->translate('Daemon Sets'),
        'url'         => 'kubernetes/daemonsets',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Stateful Sets'),
    [
        'description' => $this->translate('Stateful Sets'),
        'url'         => 'kubernetes/statefulsets',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Pods'),
    [
        'description' => $this->translate('Pods'),
        'url'         => 'kubernetes/pods',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Persistent Volumes'),
    [
        'description' => $this->translate('Persistent Volumes'),
        'url'         => 'kubernetes/persistentvolumes',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Persistent Volume Claims'),
    [
        'description' => $this->translate('Persistent Volume Claims'),
        'url'         => 'kubernetes/persistentvolumeclaims',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Cron Jobs'),
    [
        'description' => $this->translate('Cron Jobs'),
        'url'         => 'kubernetes/cronjobs',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Jobs'),
    [
        'description' => $this->translate('Jobs'),
        'url'         => 'kubernetes/jobs',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Services'),
    [
        'description' => $this->translate('Services'),
        'url'         => 'kubernetes/services',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Ingresses'),
    [
        'description' => $this->translate('Ingresses'),
        'url'         => 'kubernetes/ingresses',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Config Maps'),
    [
        'description' => $this->translate('Config Maps'),
        'url'         => 'kubernetes/configmaps',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Secrets'),
    [
        'description' => $this->translate('Secrets'),
        'url'         => 'kubernetes/secrets',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Events'),
    [
        'description' => $this->translate('Events'),
        'url'         => 'kubernetes/events',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Graphs'),
    [
        'description' => $this->translate('Graphs'),
        'url'         => 'kubernetes/graphs',
        'priority'    => $priority++
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

$this->provideJsFile('vendor/chart.umd.js');
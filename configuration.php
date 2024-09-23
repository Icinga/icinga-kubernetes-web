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
    N_('Cluster Services'),
    [
        'description' => $this->translate('Cluster Services'),
        'url'         => 'kubernetes/services?label.name=kubernetes.io%2Fcluster-service&label.value=true',
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
    N_('Cron Jobs'),
    [
        'description' => $this->translate('Cron Jobs'),
        'url'         => 'kubernetes/cronjobs',
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
    N_('Deployments'),
    [
        'description' => $this->translate('Deployments'),
        'url'         => 'kubernetes/deployments',
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
    N_('Ingresses'),
    [
        'description' => $this->translate('Ingresses'),
        'url'         => 'kubernetes/ingresses',
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
    N_('Namespaces'),
    [
        'description' => $this->translate('Namespaces'),
        'url'         => 'kubernetes/namespaces',
        'priority'    => $priority++
    ]
);

$section->add(
    N_('Nodes'),
    [
        'description' => $this->translate('Nodes'),
        'url'         => 'kubernetes/nodes',
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
    N_('Persistent Volumes'),
    [
        'description' => $this->translate('Persistent Volumes'),
        'url'         => 'kubernetes/persistentvolumes',
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
    N_('Replica Sets'),
    [
        'description' => $this->translate('Replica Sets'),
        'url'         => 'kubernetes/replicasets',
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
    N_('Services'),
    [
        'description' => $this->translate('Services'),
        'url'         => 'kubernetes/services',
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

$this->provideConfigTab(
    'database',
    [
        'title' => $this->translate('Database'),
        'label' => $this->translate('Database'),
        'url'   => 'config/database'
    ]
);

$this->providePermission(
    'kubernetes/list/config-maps',
    $this->translate('Allow to list config maps')
);

$this->providePermission(
    'kubernetes/list/cron-jobs',
    $this->translate('Allow to list cron jobs')
);

$this->providePermission(
    'kubernetes/list/daemon-sets',
    $this->translate('Allow to list daemon sets')
);

$this->providePermission(
    'kubernetes/list/deployments',
    $this->translate('Allow to list deployments')
);

$this->providePermission(
    'kubernetes/list/events',
    $this->translate('Allow to list events')
);

$this->providePermission(
    'kubernetes/list/ingresses',
    $this->translate('Allow to list ingresses')
);

$this->providePermission(
    'kubernetes/list/jobs',
    $this->translate('Allow to list jobs')
);

$this->providePermission(
    'kubernetes/list/nodes',
    $this->translate('Allow to list nodes')
);

$this->providePermission(
    'kubernetes/list/persistent-volume-claims',
    $this->translate('Allow to list persistent volume claims')
);

$this->providePermission(
    'kubernetes/list/persistent-volumes',
    $this->translate('Allow to list persistent volumes')
);

$this->providePermission(
    'kubernetes/list/pods',
    $this->translate('Allow to list pods')
);

$this->providePermission(
    'kubernetes/list/replica-sets',
    $this->translate('Allow to list replica sets')
);

$this->providePermission(
    'kubernetes/list/secretes',
    $this->translate('Allow to list secretes')
);

$this->providePermission(
    'kubernetes/list/services',
    $this->translate('Allow to list services')
);

$this->providePermission(
    'kubernetes/list/stateful-sets',
    $this->translate('Allow to list stateful sets')
);

$this->providePermission(
    'kubernetes/show-yaml',
    $this->translate('Allow to show yaml')
);

$this->provideRestriction(
    'kubernetes/filter/resources',
    $this->translate('Restrict access to the resources that match the filter')
);

if (! \Icinga\Application\Icinga::app()->getModuleManager()->hasEnabled('icingadb')) {
    $this->provideJsFile('action-list.js');
}

$this->provideJsFile('vendor/chart.umd.js');

$this->provideCssFile('action-list.less');
$this->provideCssFile('charts.less');
$this->provideCssFile('common.less');
$this->provideCssFile('conditions.less');
$this->provideCssFile('icons.less');
$this->provideCssFile('labels.less');
$this->provideCssFile('lists.less');
$this->provideCssFile('widgets.less');

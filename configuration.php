<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

/** @var Module $this */

use Icinga\Application\Modules\Module;
use Icinga\Module\Kubernetes\Common\Auth;

$section = $this->menuSection(
    'Kubernetes',
    [
        'icon' => 'globe',
        'url'  => 'kubernetes/dashboard',
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

if (Module::exists('notifications')) {
    $this->provideConfigTab(
        'notifications',
        [
            'title' => $this->translate('Notifications'),
            'label' => $this->translate('Notifications'),
            'url'   => 'config/notifications'
        ]
    );
}

$this->provideConfigTab(
    'prometheus',
    [
        'title' => $this->translate('Prometheus'),
        'label' => $this->translate('Prometheus'),
        'url'   => 'config/prometheus'
    ]
);

$this->providePermission(
    Auth::SHOW_CONFIG_MAPS,
    $this->translate('Allow to show config maps')
);

$this->providePermission(
    Auth::SHOW_CRON_JOBS,
    $this->translate('Allow to show cron jobs')
);

$this->providePermission(
    Auth::SHOW_DAEMON_SETS,
    $this->translate('Allow to show daemon sets')
);

$this->providePermission(
    Auth::SHOW_DEPLOYMENTS,
    $this->translate('Allow to show deployments')
);

$this->providePermission(
    Auth::SHOW_EVENTS,
    $this->translate('Allow to show events')
);

$this->providePermission(
    Auth::SHOW_INGRESSES,
    $this->translate('Allow to show ingresses')
);

$this->providePermission(
    Auth::SHOW_JOBS,
    $this->translate('Allow to show jobs')
);

$this->providePermission(
    Auth::SHOW_NODES,
    $this->translate('Allow to show nodes')
);

$this->providePermission(
    Auth::SHOW_PERSISTENT_VOLUME_CLAIMS,
    $this->translate('Allow to show persistent volume claims')
);

$this->providePermission(
    Auth::SHOW_PERSISTENT_VOLUMES,
    $this->translate('Allow to show persistent volumes')
);

$this->providePermission(
    Auth::SHOW_PODS,
    $this->translate('Allow to show pods')
);

$this->providePermission(
    Auth::SHOW_REPLICA_SETS,
    $this->translate('Allow to show replica sets')
);

$this->providePermission(
    Auth::SHOW_SECRETS,
    $this->translate('Allow to show secrets')
);

$this->providePermission(
    Auth::SHOW_SERVICES,
    $this->translate('Allow to show services')
);

$this->providePermission(
    Auth::SHOW_STATEFUL_SETS,
    $this->translate('Allow to show stateful sets')
);

$this->providePermission(
    'kubernetes/yaml/show',
    $this->translate('Allow to show yaml')
);

$this->provideRestriction(
    'kubernetes/filter/resources',
    $this->translate('Restrict access to the resources that match the filter')
);

$this->provideJsFile('action-list.js');
$this->provideJsFile('vendor/chart.umd.js');

$this->provideCssFile('action-list.less');
$this->provideCssFile('charts.less');
$this->provideCssFile('common.less');
$this->provideCssFile('conditions.less');
$this->provideCssFile('icons.less');
$this->provideCssFile('labels.less');
$this->provideCssFile('lists.less');
$this->provideCssFile('quick-actions.less');
$this->provideCssFile('widgets.less');
$this->provideCssFile('environment-widget.less');

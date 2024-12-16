<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class WorkloadsDashboard extends Dashboard
{
    protected function getTitle(): string
    {
        return $this->translate('Workloads');
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Dashlet(
                'cronjob',
                $this->translate('Cron Jobs'),
                $this->translate('Schedule Jobs to run at specific times.')
            ),
            new IcingaStateDashlet(
                'daemonset',
                $this->translate('Daemon Sets'),
                $this->translate(
                    'Out of {total} total Daemon Sets, {ok} are in OK state, {critical} are Critical,
                     {warning} are in Warning state, and {unknown} are Unknown.'
                )
            ),
            new IcingaStateDashlet(
                'deployment',
                $this->translate('Deployments'),
                $this->translate(
                    'Out of {total} total Deployments, {ok} are in OK state, {critical} are Critical,
                     {warning} are in Warning state, and {unknown} are Unknown.'
                )
            ),
            new IcingaStateDashlet(
                'job',
                $this->translate('Jobs'),
                $this->translate(
                    'Out of {total} total Jobs, {ok} are in OK state, {critical} are Critical, {warning} are in
                    Warning state, {unknown} are Unknown and {pending} are in Pending State.'
                )
            ),
            new IcingaStateDashlet(
                'pod',
                $this->translate('Pods'),
                $this->translate(
                    'Out of {total} total Pods, {ok} are in OK state, {critical} are Critical, {warning} are in
                    Warning state, {unknown} are Unknown, and {pending} are in Pending State.'
                )
            ),
            new IcingaStateDashlet(
                'replicaset',
                $this->translate('Replica Sets'),
                $this->translate(
                    'Out of {total} total Replica Sets, {ok} are in OK state, {critical} are Critical,
                     {warning} are in Warning state, and {unknown} are Unknown.'
                )
            ),
            new IcingaStateDashlet(
                'statefulset',
                $this->translate('Stateful Sets'),
                $this->translate(
                    'Out of {total} total Stateful Sets, {ok} are in OK state, {critical} are Critical,
                    {warning} are in Warning state, and {unknown} are Unknown.'
                )
            )
        );
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class StorageDashboard extends Dashboard
{
    public function getTitle(): string
    {
        return $this->translate('Storage');
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new KubernetesPhaseDashlet(
                'persistentvolume',
                $this->translate('Persistent Volumes'),
                $this->translate(
                    'Out of {total} total Persistent Volumes, {Bound} are Bound, {Available} are Available,
                    {Pending} are Pending, {Released} are Released, and {Failed} are Failed.'
                )
            ),
            new KubernetesPhaseDashlet(
                'persistentvolumeclaim',
                $this->translate('PVCs'),
                $this->translate(
                    'Out of {total} total Persistent Volume Claims, {Bound} are Bound, {Pending} are Pending,
                    {Lost} are Lost.'
                )
            )
        );
    }
}

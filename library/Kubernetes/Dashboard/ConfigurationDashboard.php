<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

class ConfigurationDashboard extends Dashboard
{
    public function getTitle(): string
    {
        return $this->translate('Configuration');
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Dashlet(
                'configmap',
                $this->translate('Config Maps'),
                $this->translate('Store configuration data as key-value pairs.')
            ),
            new Dashlet(
                'secret',
                $this->translate('Secrets'),
                $this->translate('Store sensitive data (e.g., passwords, tokens) in an encrypted format.')
            )
        );
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use ipl\I18n\Translation;

class SecretDashlet extends Dashlet
{
    use Translation;

    protected $icon = 'icon kicon-secret';

    public function getTitle()
    {
        return $this->translate('Secrets');
    }

    public function getSummary()
    {
        return $this->translate('Store sensitive data (e.g., passwords, tokens) in an encrypted format');
    }

    public function getUrl()
    {
        return 'kubernetes/secrets';
    }
}

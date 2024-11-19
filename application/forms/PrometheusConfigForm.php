<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Controllers\ConfigController;
use Icinga\Module\Kubernetes\Model\Config;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatForm;

class PrometheusConfigForm extends CompatForm
{
    protected function assemble(): void
    {
        if ($this->isLocked()) {
            $this->addHtml(
                Html::tag('div', Attributes::create(['class' => 'control-group']), [
                    Html::tag(
                        'div',
                        Attributes::create(['class' => 'control-label-group']),
                    ),
                    Html::tag(
                        'p',
                        Attributes::create(),
                        "Prometheus configuration is provided via YAML."
                    )
                ])
            );
        }

        $this->addElement(
            'text',
            'prometheus_url',
            [
                'label'    => $this->translate('URL'),
                'required' => true,
                'disabled' => $this->isLocked(),
            ]
        );

        $this->addElement(
            'text',
            'prometheus_username',
            [
                'label'    => $this->translate('Username'),
                'disabled' => $this->isLocked(),
            ]
        );

        $this->addElement(
            'password',
            'prometheus_password',
            [
                'label'    => $this->translate('Password'),
                'disabled' => $this->isLocked(),
            ]
        );

        $this->addElement(
            'submit',
            'submit',
            [
                'label'    => $this->translate('Save Changes'),
                'disabled' => $this->isLocked()
            ]
        );
    }

    public function isLocked(): bool
    {
        $config = Config::on(Database::connection());
        $config->filter(Filter::equal('key', ConfigController::PROMETHEUS_URL));

        $temp = $config->first();

        if (isset($config->first()->locked) && $config->first()->locked === 'y') {
            return true;
        }

        return false;
    }
}

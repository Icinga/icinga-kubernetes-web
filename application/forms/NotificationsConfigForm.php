<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use Icinga\Data\ResourceFactory;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use Icinga\Module\Kubernetes\Model\Config;
use ipl\Stdlib\Filter;
use Icinga\Module\Kubernetes\Common\Database;

class NotificationsConfigForm extends CompatForm
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
                        "Notifications configuration is provided via YAML."
                    )
                ])
            );
        }

        $this->addElement(
            'text',
            'notifications_url',
            [
                'label'    => $this->translate('URL'),
                'required' => true,
                'disabled' => $this->isLocked(),
                'value'    => ''
            ]
        );

        $this->addElement(
            'text',
            'notifications_kubernetes_web_url',
            [
                'label'    => $this->translate('Kubernetes Web URL'),
//                'required' => true,
                'disabled' => $this->isLocked(),
                'value'    => ''
            ]
        );

        $this->addElement(
            'text',
            'notifications_username',
            [
                'label'    => $this->translate('Username'),
                'required' => true,
                'disabled' => $this->isLocked(),
                'value'    => ''
            ]
        );

        $this->addElement(
            'password',
            'notifications_password',
            [
                'label'    => $this->translate('Password'),
                'required' => true,
                'disabled' => $this->isLocked(),
                'value'    => ''
            ]
        );

        $this->addElement(
            'submit',
            'submit',
            [
                'label' => $this->translate('Save Changes'),
                'disabled' => $this->isLocked()
            ]
        );
    }

    public function isLocked(): bool
    {
        $config = Config::on(Database::connection());
        $config->filter(Filter::equal('key', 'notifications.locked'));

        if ($config->first()->value === 'true') {
            return true;
        }

        return false;
    }
}

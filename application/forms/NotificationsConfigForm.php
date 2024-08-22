<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Web\Compat\CompatForm;
use Icinga\Module\Kubernetes\Model\Config;
use ipl\Stdlib\Filter;
use Icinga\Module\Kubernetes\Common\Database;

class NotificationsConfigForm extends CompatForm
{
    protected function assemble(): void
    {
        $submit = $this->createElement('submit', 'submit', [
            'label'    => $this->translate('Save Changes'),
            'disabled' => $this->isLocked() || $this->sourceAlreadyExists()
        ]);
        $this->registerElement($submit);
        $this->decorate($submit);

        $remove = $this->createElement('submit', 'remove', [
            'label'          => $this->translate('Remove'),
            'class'          => 'btn-remove',
        ]);
        $this->registerElement($remove);
        $this->decorate($remove);

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

        if ($this->sourceAlreadyExists()) {
            $this->addHtml(
                Html::tag('div', Attributes::create(['class' => 'control-group']), [
                    Html::tag(
                        'div',
                        Attributes::create(['class' => 'control-label-group']),
                    ),
                    Html::tag(
                        'p',
                        Attributes::create(),
                        "Notifications configuration has already a source."
                    )
                ])
            );
        }

        $this->addElement('text', 'notifications_url',
            [
                'label'    => $this->translate('URL'),
                'required' => true,
                'disabled' => $this->isLocked() || $this->sourceAlreadyExists(),
                'value'    => ''
            ]
        );

        $this->addElement('text', 'notifications_kubernetes_web_url',
            [
                'label'    => $this->translate('Kubernetes Web URL'),
                'required' => true,
                'disabled' => $this->isLocked() || $this->sourceAlreadyExists(),
                'value'    => ''
            ]
        );

        if (! $this->sourceAlreadyExists()) {
            $this->addHtml($submit);
        } else {
            $this->addHtml($remove);
        }
    }

    public function hasBeenSubmitted(): bool
    {
        return $this->hasBeenSent() && ($this->getPopulatedValue('submit') || $this->getPopulatedValue('remove'));
    }

    public function isLocked(): bool
    {
        $config = Config::on(Database::connection());
        $config->filter(Filter::equal('key', 'notifications.locked'));

        if (isset($config->first()->value) && $config->first()->value === 'true') {
            return true;
        }

        return false;
    }

    public function sourceAlreadyExists()
    {
        $config = Config::on(Database::connection());
        $config->filter(Filter::equal('key', 'notifications.source_id'));

        if (isset($config->first()->value)) {
            return true;
        }

        return false;
    }
}

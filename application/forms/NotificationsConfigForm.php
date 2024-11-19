<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use Icinga\Application\Icinga;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Stdlib\Str;
use ipl\Validator\CallbackValidator;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class NotificationsConfigForm extends CompatForm
{
    protected array $kconfig = [];

    public static function transformKeyForForm(string $key): string
    {
        return strtr($key, ['notifications.' => 'notifications_']);
    }

    public static function transformKeyForDb(string $key): string
    {
        return strtr($key, ['notifications_' => 'notifications.']);
    }

    public function getKConfig(): array
    {
        return $this->kconfig;
    }

    public function setKConfig(array $kconfig)
    {
        $this->kconfig = $kconfig;

        return $this;
    }

    public function isLocked(): bool
    {
        $this->ensureAssembled();

        return array_reduce(
            array_map(
                fn($element) => $element->getAttributes()->get('disabled')->getValue(),
                $this->getElements()
            ),
            fn(bool $carry, bool $item) => $carry && $item,
            true
        );
    }

    public function getValue($name, $default = null)
    {
        return parent::getValue(static::transformKeyForForm($name), $default);
    }

    public function getValues(): array
    {
        $values = parent::getValues();

        return array_combine(array_map([static::class, 'transformKeyForDb'], array_keys($values)), $values);
    }

    protected function getKubernetesWebUrl(): string
    {
        $r = Icinga::app()->getRequest();

        list($host, $port) = Str::symmetricSplit($r->getServer('HTTP_HOST'), ':', 2);

        $url = (Url::fromPath('kubernetes', [], $r))
            ->setScheme($r->getScheme())
            ->setHost($host)
            ->setIsExternal();

        if ($port !== null) {
            $url->setPort($port);
        }

        return $url->getAbsoluteUrl();
    }

    protected function isValidUri(string $uri): string
    {
        $components = parse_url($uri);

        return $components !== false && isset($components['scheme'], $components['host']);
    }

//    public function hasBeenSubmitted(): bool
//    {
//        return $this->hasBeenSent() && ($this->getPopulatedValue('submit') || $this->getPopulatedValue('remove'));
//    }

    protected function assemble(): void
    {
        $this->addElement('text', static::transformKeyForForm(KConfig::NOTIFICATIONS_URL), [
            'label'       => $this->translate('Icinga Notifications URL'),
            'disabled'    => $this->kconfig[KConfig::NOTIFICATIONS_URL]->locked ?? false,
            'value'       => $this->kconfig[KConfig::NOTIFICATIONS_URL]->value ?? null,
            'placeholder' => 'http://localhost:5680',
            'description' => $this->translate(
                'Icinga Notifications URL. Leave/set blank to turn off notifications.'
            ),
            'validators'  => [
                'Callback' => function ($value, CallbackValidator $validator) {
                    if (! $this->isValidUri($value)) {
                        $validator->addMessage($this->translate(
                            'Invalid URL: Both host and scheme must be specified.'
                        ));

                        return false;
                    }

                    return true;
                }
            ]
        ]);

        $this->addElement('text', static::transformKeyForForm(KConfig::NOTIFICATIONS_KUBERNETES_WEB_URL), [
            'label'      => $this->translate('Kubernetes Web URL'),
            'required'   => true,
            'disabled'   => $this->kconfig[KConfig::NOTIFICATIONS_KUBERNETES_WEB_URL]->locked ?? false,
            'value'      => $this->kconfig[KConfig::NOTIFICATIONS_KUBERNETES_WEB_URL]->value ?? $this->getKubernetesWebUrl(),
            'description' => $this->translate(
                'Icinga Kubernetes Web URL.'
            ),
            'validators' => [
                'Callback' => function ($value, CallbackValidator $validator) {
                    if (! $this->isValidUri($value)) {
                        $validator->addMessage($this->translate(
                            'Invalid URL: Both host and scheme must be specified.'
                        ));

                        return false;
                    }

                    return true;
                }
            ]
        ]);

        $locked = $this->isLocked();

        if ($locked) {
            // TODO(el): Add locked info box.
        }

        $this->addElement('submit', 'submit', [
            'label'    => $this->translate('Save Changes'),
            'disabled' => $locked
        ]);
    }
}

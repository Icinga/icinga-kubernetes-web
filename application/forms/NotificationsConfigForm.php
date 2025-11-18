<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use Icinga\Application\Icinga;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Web\Widget\ClusterForm;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;
use ipl\Validator\CallbackValidator;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;
use Ramsey\Uuid\Uuid;

class NotificationsConfigForm extends CompatForm
{
    public static function transformKeyForForm(string $key): string
    {
        return strtr($key, ['notifications.' => 'notifications_']);
    }

    public static function transformKeyForDb(string $key): string
    {
        return strtr($key, ['notifications_' => 'notifications.']);
    }

    public function getClusterUuid(): string
    {
        return $this->getPopulatedValue('cluster_uuid') ??
            (string) Uuid::fromBytes(Cluster::on(Database::connection())->orderBy('uuid')->first()->uuid);
    }

    public function getOldClusterUuid(): string
    {
        return $this->getPopulatedValue('old_cluster_uuid', $this->getClusterUuid());
    }

    public function getKConfig(string $clusterUuid): array
    {
        $kconfig = [];
        $q = KConfig::on(Database::connection())
            ->filter(Filter::equal('key', [
                KConfig::NOTIFICATIONS_URL,
                KConfig::NOTIFICATIONS_USERNAME,
                KConfig::NOTIFICATIONS_PASSWORD,
                KConfig::NOTIFICATIONS_KUBERNETES_WEB_URL
            ]))
            ->filter(Filter::equal('cluster_uuid', $clusterUuid));

        foreach ($q as $r) {
            $kconfig[$r['key']] = $r;
        }

        return $kconfig;
    }

    public function isLocked(): bool
    {
        $this->ensureAssembled();

        return array_reduce(
            array_map(
                fn($element) => $element->getAttributes()->get('disabled')->getValue(),
                array_filter($this->getElements(), fn($element) => ! $element->isIgnored())
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
        $clusterUuid = $this->getClusterUuid();

        if ($clusterUuid !== $this->getOldClusterUuid()) {
            $this->clearPopulatedValue('old_cluster_uuid');
            $this->clearPopulatedValue(static::transformKeyForForm(KConfig::NOTIFICATIONS_URL));
            $this->clearPopulatedValue(static::transformKeyForForm(KConfig::NOTIFICATIONS_KUBERNETES_WEB_URL));
        }

        $this->addElement('hidden', 'old_cluster_uuid', ['value' => $clusterUuid, 'ignore' => true]);

        $this->addElement(
            'select',
            'cluster_uuid',
            [
                'required' => true,
                'class'    => 'autosubmit',
                'label'    => $this->translate('Cluster'),
                'options'  => iterator_to_array(ClusterForm::yieldClusters()),
                'value'    => $clusterUuid,
                'ignore'   => true
            ],
        );

        $kconfig = $this->getKConfig($clusterUuid);

        $this->addElement('text', static::transformKeyForForm(KConfig::NOTIFICATIONS_URL), [
            'required'    => true,
            'label'       => $this->translate('Icinga Notifications URL'),
            'disabled'    => $kconfig[KConfig::NOTIFICATIONS_URL]->locked ?? false,
            'value'       => $kconfig[KConfig::NOTIFICATIONS_URL]->value ?? null,
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
            'label'       => $this->translate('Kubernetes Web URL'),
            'required'    => true,
            'disabled'    => $kconfig[KConfig::NOTIFICATIONS_KUBERNETES_WEB_URL]->locked ?? false,
            'value'       => $kconfig[KConfig::NOTIFICATIONS_KUBERNETES_WEB_URL]->value ??
                $this->getKubernetesWebUrl(),
            'description' => $this->translate(
                'Icinga Kubernetes Web URL.'
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

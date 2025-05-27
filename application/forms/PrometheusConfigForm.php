<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Web\Widget\ClusterForm;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatForm;
use Ramsey\Uuid\Uuid;

class PrometheusConfigForm extends CompatForm
{
    public function getClusterUuid(): string
    {
        return $this->getPopulatedValue('cluster_uuid') ??
            (string) Uuid::fromBytes(Cluster::on(Database::connection())->orderBy('uuid')->first()->uuid);
    }

    public function getKConfig(string $clusterUuid): array
    {
        $kconfig = [];
        $q = KConfig::on(Database::connection())
            ->filter(Filter::equal('key', [
                KConfig::PROMETHEUS_URL,
                KConfig::PROMETHEUS_INSECURE,
                KConfig::PROMETHEUS_USERNAME,
                KConfig::PROMETHEUS_PASSWORD
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
        return parent::getValue(KConfig::transformKeyForForm($name), $default);
    }

    public function getValues(): array
    {
        $values = parent::getValues();

        return array_combine(array_map([KConfig::class, 'transformKeyForDb'], array_keys($values)), $values);
    }

    protected function assemble(): void
    {
        $clusterUuid = $this->getPopulatedValue('cluster_uuid') ??
            (string) Uuid::fromBytes(Cluster::on(Database::connection())->orderBy('uuid')->first()->uuid);

        if ($clusterUuid !== $this->getPopulatedValue('old_cluster_uuid', $clusterUuid)) {
            $this->clearPopulatedValue('old_cluster_uuid');
            $this->clearPopulatedValue('prometheus_url');
            $this->clearPopulatedValue('prometheus_insecure');
            $this->clearPopulatedValue('prometheus_username');
            $this->clearPopulatedValue('prometheus_password');
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

        $this->addElement(
            'text',
            KConfig::transformKeyForForm(KConfig::PROMETHEUS_URL),
            [
                'label'    => $this->translate('URL'),
                'required' => true,
                'value'    => $kconfig[KConfig::PROMETHEUS_URL]->value ?? null,
                'disabled' => $kconfig[KConfig::PROMETHEUS_URL]->locked ?? false,
                'ignore'   => $kconfig[KConfig::PROMETHEUS_URL]->locked ?? false,
            ]
        );

        $this->addElement(
            'checkbox',
            KConfig::transformKeyForForm(KConfig::PROMETHEUS_INSECURE),
            [
                'label'          => $this->translate('Insecure'),
                'checkedValue'   => 'true',
                'uncheckedValue' => 'false',
                'value'          => $kconfig[KConfig::PROMETHEUS_INSECURE]?->value === 'true',
                'disabled'       => $kconfig[KConfig::PROMETHEUS_INSECURE]->locked ?? false,
                'ignore'         => $kconfig[KConfig::PROMETHEUS_INSECURE]->locked ?? false,
            ]
        );

        $this->addElement(
            'text',
            KConfig::transformKeyForForm(KConfig::PROMETHEUS_USERNAME),
            [
                'label'    => $this->translate('Username'),
                'value'    => $kconfig[KConfig::PROMETHEUS_USERNAME]->value ?? null,
                'disabled' => $kconfig[KConfig::PROMETHEUS_USERNAME]->locked ?? false,
                'ignore'   => $kconfig[KConfig::PROMETHEUS_USERNAME]->locked ?? false,
            ]
        );

        $this->addElement(
            'password',
            KConfig::transformKeyForForm(KConfig::PROMETHEUS_PASSWORD),
            [
                'label'    => $this->translate('Password'),
                'value'    => $kconfig[KConfig::PROMETHEUS_PASSWORD]->value ?? null,
                'disabled' => $kconfig[KConfig::PROMETHEUS_PASSWORD]->locked ?? false,
                'ignore'   => $kconfig[KConfig::PROMETHEUS_PASSWORD]->locked ?? false,
            ]
        );

        $this->addElement(
            'submit',
            'submit',
            [
                'label'    => $this->translate('Save Changes'),
                'disabled' => $this->isLocked(),
            ]
        );
    }
}

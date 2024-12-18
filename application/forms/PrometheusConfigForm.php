<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Controllers\ConfigController;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\Model\Config;
use Icinga\Module\Kubernetes\Web\ClusterForm;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatForm;
use Ramsey\Uuid\Uuid;

class PrometheusConfigForm extends CompatForm
{
    protected function assemble(): void
    {
        $clusterUuid = $this->getPopulatedValue('cluster_uuid') ??
            (string) Uuid::fromBytes(Cluster::on(Database::connection())->orderBy('uuid')->first()->uuid);

        $this->addElement('hidden', 'old_cluster_uuid', ['value' => $clusterUuid]);

        if ($clusterUuid !== $this->getPopulatedValue('old_cluster_uuid', $clusterUuid)) {
            $this->clearPopulatedValue('prometheus_url');
        }

        $dbConfig = Config::on(Database::connection())->filter(
            Filter::all(
                Filter::equal('cluster_uuid', Uuid::fromString($clusterUuid)->getBytes()),
                Filter::any(
                    Filter::equal('key', ConfigController::PROMETHEUS_URL),
                    Filter::equal('key', ConfigController::PROMETHEUS_USERNAME),
                    Filter::equal('key', ConfigController::PROMETHEUS_PASSWORD)
                )
            )
        );

        $data = [];

        foreach ($dbConfig as $pair) {
            $data[$pair->key] = ['value' => $pair->value, 'locked' => $pair->locked];
        }

        $this->addElement(
            'select',
            'cluster_uuid',
            [
                'required' => true,
                'class'    => 'autosubmit',
                'label'    => $this->translate('Cluster'),
                'options'  => iterator_to_array(ClusterForm::yieldClusters()),
                'value'    => $clusterUuid,
            ],
        );

        if ($this->isLocked($clusterUuid)) {
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
                'value'    => $data[ConfigController::PROMETHEUS_URL]['value'] ?? '',
                'disabled' => $this->isLocked($clusterUuid),
            ]
        );

        $this->addElement(
            'text',
            'prometheus_username',
            [
                'label'    => $this->translate('Username'),
                'value'    => $data[ConfigController::PROMETHEUS_USERNAME]['value'] ?? '',
                'disabled' => $this->isLocked($clusterUuid),
            ]
        );

        $this->addElement(
            'password',
            'prometheus_password',
            [
                'label'    => $this->translate('Password'),
                'value'    => $data[ConfigController::PROMETHEUS_PASSWORD]['value'] ?? '',
                'disabled' => $this->isLocked($clusterUuid),
            ]
        );

        $this->addElement(
            'submit',
            'submit',
            [
                'label'    => $this->translate('Save Changes'),
                'disabled' => $this->isLocked($clusterUuid),
            ]
        );
    }

    public function isLocked(string $clusterUuid): bool
    {
        $config = Config::on(Database::connection())
            ->filter(
                Filter::all(
                    Filter::equal('cluster_uuid', Uuid::fromString($clusterUuid)->getBytes()),
                    Filter::equal('key', ConfigController::PROMETHEUS_URL),
                )
            )
            ->first();

        return $config->locked ?? false;
    }
}

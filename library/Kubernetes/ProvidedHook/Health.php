<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\ProvidedHook;

use Icinga\Application\Hook\HealthHook;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Instance;
use ipl\I18n\Translation;

class Health extends HealthHook
{
    use Translation;

    public function getName(): string
    {
        return 'Icinga for Kubernetes';
    }

    public function checkHealth(): void
    {
        $instance = $this->getInstance();

        if ($instance !== null) {
            $this->setMetrics([
                'version'                  => $instance->version,
                'kubernetes_version'       => $instance->kubernetes_version,
                'kubernetes_heartbeat'     => $instance->kubernetes_heartbeat->getTimestamp(),
                'kubernetes_api_reachable' => $instance->kubernetes_api_reachable,
                'heartbeat'                => $instance->heartbeat->getTimestamp(),
            ]);
        }

        if (
            $instance === null
            || $instance->heartbeat->getTimestamp() < time() - 60
        ) {
            $this->setState(self::STATE_UNKNOWN);
            $this->setMessage($this->translate(
                'Icinga for Kubernetes is not running or not writing into the database.'
            ));

            return;
        }

        if (
            ! $instance->kubernetes_api_reachable
            || $instance->kubernetes_heartbeat->getTimestamp() < time() - 60
        ) {
            $this->setState(self::STATE_CRITICAL);

            $message = [
                $this->translate('Icinga for Kubernetes is not connected to Kubernetes.'),
                $instance->message
            ];

            $this->setMessage(implode(' ', array_filter($message)));

            return;
        }

        $this->setState(self::STATE_OK);
        $this->setMessage($this->translate(
            'Icinga for Kubernetes is running, connected to Kubernetes, and writing into the database.'
        ));
    }

    protected function getInstance(): ?Instance
    {
        return Instance::on(Database::connection())->first();
    }
}

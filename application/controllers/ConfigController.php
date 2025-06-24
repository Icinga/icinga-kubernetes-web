<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Forms\DatabaseConfigForm;
use Icinga\Module\Kubernetes\Forms\NotificationsConfigForm;
use Icinga\Module\Kubernetes\Forms\PrometheusConfigForm;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Notifications\Common\Database as NotificationsDatabase;
use Icinga\Module\Notifications\Forms\SourceForm;
use Icinga\Module\Notifications\Model\Source;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use Icinga\Web\Widget\Tabs;
use ipl\Sql\Connection;
use ipl\Stdlib\Filter;
use ipl\Web\Url;
use Ramsey\Uuid\Uuid;
use Throwable;

class ConfigController extends Controller
{
    protected bool $disableDefaultAutoRefresh = true;

    public function init()
    {
        $this->assertPermission('config/modules');

        parent::init();
    }

    public function databaseAction(): void
    {
        $this->mergeTabs($this->Module()->getConfigTabs()->activate('database'));

        $config = Config::module('kubernetes');
        $form = (new DatabaseConfigForm())
            ->populate($config->getSection('database'))
            ->on(DatabaseConfigForm::ON_SUBMIT, function (DatabaseConfigForm $form) use ($config) {
                $config->setSection('database', $form->getValues());
                $config->saveIni();

                Notification::success($this->translate('New configuration has successfully been stored'));
            })->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }

    public function notificationsAction()
    {
        $this->mergeTabs($this->Module()->getConfigTabs()->activate('notifications'));

        $sourceForm = new class (NotificationsDatabase::get()) extends SourceForm {
            public function hasBeenSent(): bool
            {
                return true;
            }

            public function hasBeenSubmitted(): bool
            {
                return true;
            }

            public function onError()
            {
                $messages = [];
                foreach ($this->getMessages() as $message) {
                    if ($message instanceof Throwable) {
                        $messages[] = $message->getMessage();
                    } else {
                        $messages[] = $message;
                    }
                }
                foreach ($this->getElements() as $element) {
                    foreach ($element->getMessages() as $message) {
                        $messages[] = $element->getName() . ": " . $message;
                    }
                }
                throw new Exception(implode("\n", array_filter($messages)));
            }
        };

        $form = (new NotificationsConfigForm())
            ->populate([
                'cluster_uuid' =>
                    $this->getRequest()->get('cluster_uuid') ??
                    Session::getSession()
                        ->getNamespace('kubernetes')
                        ->get('cluster_uuid')
            ])
            ->on(
                NotificationsConfigForm::ON_SUBMIT,
                function (NotificationsConfigForm $form) use ($sourceForm) {
                    if ($form->isLocked()) {
                        $form->addMessage($this->translate('Notifications configuration is locked.'));

                        return;
                    }

                    $clusterUuid = $form->getClusterUuid();
                    $kconfig = $form->getKConfig($clusterUuid);
                    $values = $form->getValues();

                    /** @var ?Source $source */
                    $source = Source::on(NotificationsDatabase::get())
                        ->filter(Filter::all(
                            Filter::equal('name', KConfig::DEFAULT_NOTIFICATIONS_NAME . " ($clusterUuid)"),
                            Filter::equal('type', KConfig::DEFAULT_NOTIFICATIONS_TYPE)
                        ))
                        ->first();

                    if (
                        $source === null
                        || ! isset($kconfig[KConfig::NOTIFICATIONS_PASSWORD])
                        // Must be kept in sync with SourceForm.
                        || password_hash(
                            $kconfig[KConfig::NOTIFICATIONS_PASSWORD]->value,
                            SourceForm::HASH_ALGORITHM
                        ) !== $source->listener_password_hash
                    ) {
                        try {
                            $values[KConfig::NOTIFICATIONS_PASSWORD] = $this
                                ->createOrUpdateSource($sourceForm, $clusterUuid);
                        } catch (Throwable $e) {
                            Logger::error($e);
                            Logger::error($e->getTraceAsString());

                            throw $e;
                        }
                    }

                    $values[KConfig::NOTIFICATIONS_USERNAME] = $clusterUuid;

                    try {
                        Database::connection()->transaction(function (Connection $db) use ($values, $clusterUuid) {
                            $key = $db->quoteIdentifier('key');

                            $db->delete((new KConfig())->getTableName(), [
                                'cluster_uuid = ?'         => Uuid::fromString($clusterUuid)->getBytes(),
                                sprintf('%s IN (?)', $key) => array_keys($values),
                                'locked = ?'               => 'n'
                            ]);

                            foreach ($values as $k => $v) {
                                if (empty($v)) {
                                    continue;
                                }

                                $db->insert((new KConfig())->getTableName(), [
                                    'cluster_uuid' => Uuid::fromString($clusterUuid)->getBytes(),
                                    $key           => $k,
                                    'value'        => $v
                                ]);
                            }
                        });
                    } catch (Throwable $e) {
                        Logger::error($e);
                        Logger::error($e->getTraceAsString());

                        throw $e;
                    }

                    Notification::success(
                        $this->translate('New configuration has successfully been stored.')
                    );

                    $this->redirectNow(
                        Url::fromPath('kubernetes/config/notifications', ['cluster_uuid' => $clusterUuid])
                    );
                }
            )->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }

    public function prometheusAction()
    {
        $form = (new PrometheusConfigForm())
            ->populate([
                'cluster_uuid' =>
                    $this->getRequest()->get('cluster_uuid') ??
                    Session::getSession()
                        ->getNamespace('kubernetes')
                        ->get('cluster_uuid')
            ])
            ->on(PrometheusConfigForm::ON_SUBMIT, function (PrometheusConfigForm $form) {
                if ($form->isLocked()) {
                    $form->addMessage($this->translate('Prometheus configuration is locked.'));

                    return;
                }

                $clusterUuid = $form->getClusterUuid();
                $values = $form->getValues();

                try {
                    Database::connection()->transaction(function (Connection $db) use ($values, $clusterUuid) {
                        $key = $db->quoteIdentifier('key');

                        $db->delete((new KConfig())->getTableName(), [
                            'cluster_uuid = ?'         => Uuid::fromString($clusterUuid)->getBytes(),
                            sprintf('%s IN (?)', $key) => array_keys($values),
                            'locked = ?'               => 'n'
                        ]);

                        foreach ($values as $k => $v) {
                            if (empty($v)) {
                                continue;
                            }

                            $db->insert((new KConfig())->getTableName(), [
                                'cluster_uuid' => Uuid::fromString($clusterUuid)->getBytes(),
                                $key           => $k,
                                'value'        => $v
                            ]);
                        }
                    });
                } catch (Throwable $e) {
                    Logger::error($e);
                    Logger::error($e->getTraceAsString());

                    throw $e;
                }

                Notification::success($this->translate('New configuration has successfully been stored'));
                $this->redirectNow(Url::fromPath('kubernetes/config/prometheus', ['cluster_uuid' => $clusterUuid]));
            })->handleRequest($this->getServerRequest());

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('prometheus'));

        $this->addContent($form);
    }

    /**
     * Merge tabs with other tabs contained in this tab panel
     *
     * @param Tabs $tabs
     */
    protected function mergeTabs(Tabs $tabs): void
    {
        foreach ($tabs->getTabs() as $tab) {
            $this->tabs->add($tab->getName(), $tab);
        }
    }

    protected function createOrUpdateSource(SourceForm $sourceForm, string $clusterUuid): string
    {
        $password = sha1(openssl_random_pseudo_bytes(16));

        $formData = [
            'listener_username'      => $clusterUuid,
            'listener_password'      => $password,
            'listener_password_dupe' => $password,
            'name'                   => KConfig::DEFAULT_NOTIFICATIONS_NAME . " ($clusterUuid)",
            'type'                   => KConfig::DEFAULT_NOTIFICATIONS_TYPE,
            // TODO(el): Why?
            'icinga2_insecure_tls'   => 'n'
        ];

        /** @var ?Source $source */
        $source = Source::on(NotificationsDatabase::get())
            ->filter(Filter::all(
                Filter::equal('name', KConfig::DEFAULT_NOTIFICATIONS_NAME . " ($clusterUuid)"),
                Filter::equal('type', KConfig::DEFAULT_NOTIFICATIONS_TYPE)
            ))
            ->first();

        if ($source !== null) {
            $sourceForm->loadSource($source->id);
        }

        $sourceForm->populate($formData);

        if ($source !== null) {
            $sourceForm->on(SourceForm::ON_SUBMIT, function (SourceForm $form) {
                $form->editSource();
            });
        } else {
            $sourceForm->on(SourceForm::ON_SUBMIT, function (SourceForm $form) {
                $form->addSource();
            });
        }

        $sourceForm->disableCsrfCounterMeasure();
        $sourceForm->handleRequest(ServerRequest::fromGlobals());

        return $password;
    }
}

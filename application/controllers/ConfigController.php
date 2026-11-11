<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Forms\DatabaseConfigForm;
use Icinga\Module\Kubernetes\Forms\NotificationsConfigForm;
use Icinga\Module\Kubernetes\Forms\PrometheusConfigForm;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Web\Controller\Controller;
use Icinga\Module\Notifications\Integrations\V1\Source;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use Icinga\Web\Widget\Tabs;
use ipl\Sql\Connection;
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

        $form = (new NotificationsConfigForm())
            ->populate([
                'cluster_uuid' =>
                    $this->getRequest()->get('cluster_uuid') ??
                    Session::getSession()
                        ->getNamespace('kubernetes')
                        ->get('cluster_uuid')
            ])
            ->on(NotificationsConfigForm::ON_SUBMIT, function (NotificationsConfigForm $form) {
                if ($form->isLocked()) {
                    $form->addMessage($this->translate('Notifications configuration is locked.'));

                    return;
                }

                $clusterUuid = $form->getClusterUuid();
                $kconfig = $form->getKConfig($clusterUuid);
                $values = array_filter(
                    $form->getValues(),
                    fn(string $key) => ! ($kconfig[$key]->locked ?? false),
                    ARRAY_FILTER_USE_KEY
                );

                $urlIsWritable = ! ($kconfig[KConfig::NOTIFICATIONS_URL]->locked ?? false);
                $usernameIsLocked = $kconfig[KConfig::NOTIFICATIONS_USERNAME]->locked ?? false;
                $passwordIsLocked = $kconfig[KConfig::NOTIFICATIONS_PASSWORD]->locked ?? false;

                $source = null;
                if ($urlIsWritable && ! empty($values[KConfig::NOTIFICATIONS_URL])) {
                    $source = Source::get(
                        $usernameIsLocked ? $kconfig[KConfig::NOTIFICATIONS_USERNAME]->value : $clusterUuid
                    );
                    $isNew = $source->getName() === null;

                    if ($passwordIsLocked) {
                        $source->setPassword($kconfig[KConfig::NOTIFICATIONS_PASSWORD]->value);
                    } elseif ($isNew || ! isset($kconfig[KConfig::NOTIFICATIONS_PASSWORD])) {
                        $password = sha1(openssl_random_pseudo_bytes(16));
                        $source->setPassword($password);
                        $values[KConfig::NOTIFICATIONS_PASSWORD] = $password;
                    }

                    if ($isNew) {
                        $source->setName(KConfig::DEFAULT_NOTIFICATIONS_NAME . " ($clusterUuid)");
                    }

                    $source->setType(KConfig::DEFAULT_NOTIFICATIONS_TYPE);
                }

                if ($urlIsWritable && ! $usernameIsLocked) {
                    $values[KConfig::NOTIFICATIONS_USERNAME] = $clusterUuid;
                }

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

                try {
                    $source?->save();
                } catch (Throwable $e) {
                    if (isset($password)) {
                        Database::connection()->delete((new KConfig())->getTableName(), [
                            'cluster_uuid = ?' => Uuid::fromString($clusterUuid)->getBytes(),
                            sprintf('%s = ?', Database::connection()->quoteIdentifier('key'))
                                               => KConfig::NOTIFICATIONS_PASSWORD,
                            'locked = ?'       => 'n',
                        ]);
                    }

                    Logger::error($e);

                    throw $e;
                }

                Notification::success(
                    $this->translate('New configuration has successfully been stored.')
                );

                $this->redirectNow(
                    Url::fromPath('kubernetes/config/notifications', ['cluster_uuid' => $clusterUuid])
                );
            })->handleRequest($this->getServerRequest());

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
}

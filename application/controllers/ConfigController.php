<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Forms\DatabaseConfigForm;
use Icinga\Module\Kubernetes\Forms\NotificationsConfigForm;
use Icinga\Module\Kubernetes\Forms\PrometheusConfigForm;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Notifications\Common\Database as NotificationsDatabase;
use Icinga\Module\Notifications\Forms\SourceForm;
use Icinga\Module\Notifications\Model\Source;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tabs;
use ipl\Sql\Connection;
use ipl\Stdlib\Filter;
use LogicException;
use Ramsey\Uuid\Uuid;
use Throwable;

class ConfigController extends Controller
{
    protected bool $disableDefaultAutoRefresh = true;

    public const PROMETHEUS_URL = 'prometheus.url';

    public const PROMETHEUS_USERNAME = 'prometheus.username';

    public const PROMETHEUS_PASSWORD = 'prometheus.password';

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
            ->on(DatabaseConfigForm::ON_SUCCESS, function (DatabaseConfigForm $form) use ($config) {
                $config->setSection('database', $form->getValues());
                $config->saveIni();

                Notification::success($this->translate('New configuration has successfully been stored'));
            })->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }

    public function notificationsAction()
    {
        $this->mergeTabs($this->Module()->getConfigTabs()->activate('notifications'));

        $kconfig = [];
        $q = KConfig::on(Database::connection())
            ->filter(Filter::equal('key', [
                KConfig::NOTIFICATIONS_URL,
                KConfig::NOTIFICATIONS_USERNAME,
                KConfig::NOTIFICATIONS_KUBERNETES_WEB_URL
            ]));

        foreach ($q as $r) {
            $kconfig[$r['key']] = $r;
        }

        $sourceForm = new class (NotificationsDatabase::get()) extends SourceForm {
            public function hasBeenSent(): bool
            {
                return true;
            }

            public function hasBeenSubmitted(): bool
            {
                return true;
            }
        };

        $form = (new NotificationsConfigForm())
            ->setKConfig($kconfig)
            ->on(
                NotificationsConfigForm::ON_SUCCESS,
                function (NotificationsConfigForm $form) use ($kconfig, $sourceForm) {
                    if ($form->isLocked()) {
                        $form->addMessage($this->translate('Notifications configuration is locked.'));

                        return;
                    }

                    $values = $form->getValues();

                    if (
                        ! ($kconfig[KConfig::NOTIFICATIONS_USERNAME]->locked ?? false)
                        && ($kconfig[KConfig::NOTIFICATIONS_USERNAME]->value ?? '') === ''
                    ) {
                        try {
                            $values[KConfig::NOTIFICATIONS_PASSWORD] = $this->createSource($sourceForm);
                        } catch (Throwable $e) {
                            Logger::error($e);
                            Logger::error($e->getTraceAsString());

                            $form->addMessage($e->getMessage());

                            return;
                        }

                        /** @var ?Source $source */
                        $source = Source::on(NotificationsDatabase::get())
                            ->filter(Filter::all(
                                Filter::equal('name', KConfig::DEFAULT_NOTIFICATIONS_NAME),
                                Filter::equal('type', KConfig::DEFAULT_NOTIFICATIONS_TYPE)
                            ))
                            ->first();

                        if ($source === null) {
                            throw new LogicException($this->translate('Source not found'));
                        }

                        $values[KConfig::NOTIFICATIONS_USERNAME] = "source-$source->id";
                    }

                    try {
                        Database::connection()->transaction(function (Connection $db) use ($values) {
                            $db->delete((new KConfig())->getTableName(), [
                                sprintf('%s IN (?)', $db->quoteIdentifier('key')) => array_keys($values),
                                'locked = ?'                                      => 'n'
                            ]);

                            foreach ($values as $k => $v) {
                                if (empty($v)) {
                                    continue;
                                }

                                $db->insert((new KConfig())->getTableName(), [
                                    $db->quoteIdentifier('key') => $k,
                                    'value'                     => $v,
                                ]);
                            }
                        });
                    } catch (Throwable $e) {
                        Logger::error($e);
                        Logger::error($e->getTraceAsString());

                        $form->addMessage($e->getMessage());

                        return;
                    }

                    Notification::success(
                        $this->translate('New configuration has successfully been stored.')
                    );

                    $this->redirectNow('__REFRESH__');
                }
            )->handleRequest($this->getServerRequest());

        if (
            preg_match(
                '/source-(\d+)/',
                $kconfig[KConfig::NOTIFICATIONS_USERNAME]->value ?? '',
                $matches
            ) !== false
            && ! empty($matches)
        ) {
            try {
                $sourceForm->loadSource($matches[1]);

                // TODO(el): Check password mismatch.
            } catch (HttpNotFoundException $e) {
                // TODO(el): Add error box.
            }
        }

        $this->addContent($form);
    }

    public function prometheusAction()
    {
        $form = (new PrometheusConfigForm())
            ->on(PrometheusConfigForm::ON_SUCCESS, function (PrometheusConfigForm $form) {
                $clusterUuid = $form->getValue('cluster_uuid');
                if ($form->isLocked($clusterUuid)) {
                    Notification::error($this->translate('Prometheus configuration is locked'));
                    return;
                }

                try {
                    $db = Database::connection();
                    $db->exec("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
                    $db->beginTransaction();

                    $dbConfig = KConfig::on($db)->filter(
                        Filter::all(
                            Filter::equal('cluster_uuid', Uuid::fromString($clusterUuid)->getBytes()),
                            Filter::any(
                                Filter::equal('key', self::PROMETHEUS_URL),
                                Filter::equal('key', self::PROMETHEUS_USERNAME),
                                Filter::equal('key', self::PROMETHEUS_PASSWORD)
                            )
                        )
                    );

                    $data = [];

                    foreach ($dbConfig as $pair) {
                        $data[$pair->key] = ['value' => $pair->value, 'locked' => $pair->locked];
                    }

                    if (isset($data[self::PROMETHEUS_URL]) && $data[self::PROMETHEUS_URL]['locked'] !== 'y') {
                        $db->update(
                            'config',
                            [
                                'value' => $form->getValue($this->fieldForForm(self::PROMETHEUS_URL))
                            ],
                            [
                                'cluster_uuid = ?'                   => Uuid::fromString($clusterUuid)->getBytes(),
                                $db->quoteIdentifier('key') . ' = ?' => self::PROMETHEUS_URL,
                            ]
                        );
                    } elseif (! isset($data[self::PROMETHEUS_URL])) {
                        $db->insert(
                            'config',
                            [
                                'cluster_uuid'              => Uuid::fromString($clusterUuid)->getBytes(),
                                $db->quoteIdentifier('key') => self::PROMETHEUS_URL,
                                'value'                     => $form->getValue(
                                    $this->fieldForForm(self::PROMETHEUS_URL)
                                )
                            ]
                        );
                    }

                    if (isset($data[self::PROMETHEUS_USERNAME]) && $data[self::PROMETHEUS_USERNAME]['locked'] !== 'y') {
                        $db->update(
                            'config',
                            [
                                'value' => $form->getValue($this->fieldForForm(self::PROMETHEUS_USERNAME))
                            ],
                            [
                                'cluster_uuid = ?'                   => Uuid::fromString($clusterUuid)->getBytes(),
                                $db->quoteIdentifier('key') . ' = ?' => self::PROMETHEUS_USERNAME
                            ]
                        );
                    } elseif (! isset($data[self::PROMETHEUS_USERNAME])) {
                        $db->insert(
                            'config',
                            [
                                'cluster_uuid'              => Uuid::fromString($clusterUuid)->getBytes(),
                                $db->quoteIdentifier('key') => self::PROMETHEUS_USERNAME,
                                'value'                     => $form->getValue(
                                    $this->fieldForForm(self::PROMETHEUS_USERNAME)
                                )
                            ]
                        );
                    }

                    if (isset($data[self::PROMETHEUS_PASSWORD]) && $data[self::PROMETHEUS_PASSWORD]['locked'] !== 'y') {
                        $db->update(
                            'config',
                            [
                                'value' => $form->getValue($this->fieldForForm(self::PROMETHEUS_PASSWORD))
                            ],
                            [
                                'cluster_uuid = ?'                   => Uuid::fromString($clusterUuid)->getBytes(),
                                $db->quoteIdentifier('key') . ' = ?' => self::PROMETHEUS_PASSWORD
                            ]
                        );
                    } elseif (! isset($data[self::PROMETHEUS_PASSWORD])) {
                        $db->insert(
                            'config',
                            [
                                'cluster_uuid'              => Uuid::fromString($clusterUuid)->getBytes(),
                                $db->quoteIdentifier('key') => self::PROMETHEUS_PASSWORD,
                                'value'                     => $form->getValue(
                                    $this->fieldForForm(self::PROMETHEUS_PASSWORD)
                                )
                            ]
                        );
                    }

                    $db->commitTransaction();
                } catch (Exception $e) {
                    $db->rollBackTransaction();
                    Notification::error(
                        $this->translate('Failed to store new configuration') . ': ' . $e->getMessage()
                    );
                    return;
                }

                Notification::success($this->translate('New configuration has successfully been stored'));
                $this->redirectNow('__REFRESH__');
            })->handleRequest($this->getServerRequest());

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('prometheus'));

        $this->addContent($form);
    }

    /**
     * Convert database field name to form field name
     *
     * @param string $field
     *
     * @return string
     */
    protected function fieldForForm(string $field): string
    {
        return str_replace('.', '_', $field);
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

    protected function createSource(SourceForm $sourceForm): string
    {
        $password = sha1(openssl_random_pseudo_bytes(16));

        $formData = [
            'listener_password'      => $password,
            'listener_password_dupe' => $password,
            'name'                   => KConfig::DEFAULT_NOTIFICATIONS_NAME,
            'type'                   => KConfig::DEFAULT_NOTIFICATIONS_TYPE,
            // TODO(el): Why?
            'icinga2_insecure_tls'   => 'n'
        ];

        $sourceForm
            ->populate($formData)
            ->on(SourceForm::ON_SUCCESS, function (SourceForm $form) {
                $form->addSource();
            });

        $sourceForm->ensureAssembled();
        $csrf = $sourceForm->getElement('CSRFToken');
        if (preg_match('/ value="([^"]+)/', $csrf->getAttributes()->render(), $matches)) {
            $csrf->setValue($matches[1]);
        }

        $sourceForm->handleRequest(ServerRequest::fromGlobals());

        return $password;
    }
}

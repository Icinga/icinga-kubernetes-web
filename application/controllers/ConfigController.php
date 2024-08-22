<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Forms\DatabaseConfigForm;
use Icinga\Module\Kubernetes\Forms\NotificationsConfigForm;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Notifications\Forms\SourceForm;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tabs;
use ipl\Sql\Connection;
use ipl\Stdlib\Filter;
use Throwable;

class ConfigController extends Controller
{
    protected const DEFAULT_SOURCE_USER = 'Icinga for Kubernetes';

    protected const NOTIFICATIONS_SOURCE_ID = 'notifications.source_id';

    protected const NOTIFICATIONS_URL = 'notifications.url';

    protected const NOTIFICATIONS_KUBERNETES_WEB_URL = 'notifications.kubernetes_web_url';

    protected const NOTIFICATIONS_USERNAME = 'notifications.username';

    protected const NOTIFICATIONS_PASSWORD = 'notifications.password';

    protected const NOTIFICATIONS_LOCKED = 'notifications.locked';

    protected bool $disableDefaultAutoRefresh = true;

    public function init()
    {
        $this->assertPermission('config/modules');

        parent::init();
    }

    public function databaseAction(): void
    {
        $config = Config::module('kubernetes');
        $form = (new DatabaseConfigForm())
            ->populate($config->getSection('database'))
            ->on(DatabaseConfigForm::ON_SUCCESS, function ($form) use ($config) {
                $config->setSection('database', $form->getValues());
                $config->saveIni();

                Notification::success($this->translate('New configuration has successfully been stored'));
            })->handleRequest($this->getServerRequest());

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('database'));

        $this->addContent($form);
    }

    public function notificationsAction()
    {
        $db = Database::connection();
        $dbConfig = KConfig::on($db);
        $dbConfig->filter(
            Filter::any(
                Filter::equal('key', self::NOTIFICATIONS_URL),
                Filter::equal('key', self::NOTIFICATIONS_KUBERNETES_WEB_URL),
                Filter::equal('key', self::NOTIFICATIONS_USERNAME),
                Filter::equal('key', self::NOTIFICATIONS_PASSWORD),
                Filter::equal('key', self::NOTIFICATIONS_LOCKED)
            )
        );

        $data = [];

        foreach ($dbConfig as $pair) {
            switch ($pair->key) {
                case self::NOTIFICATIONS_URL:
                    $data['notifications_url'] = $pair->value;
                    break;
                case self::NOTIFICATIONS_KUBERNETES_WEB_URL:
                    $data['notifications_kubernetes_web_url'] = $pair->value;
                    break;
                case self::NOTIFICATIONS_USERNAME:
                    $data['notifications_username'] = $pair->value;
                    break;
                case self::NOTIFICATIONS_PASSWORD:
                    $data['notifications_password'] = $pair->value;
                    break;
            }
        }

        $form = (new NotificationsConfigForm())
            ->populate($data)
            ->on(NotificationsConfigForm::ON_SUCCESS,
                function (NotificationsConfigForm $form) use ($db, $dbConfig) {
                    if ($form->isLocked()) {
                        Notification::error($this->translate('Notifications configuration is locked'));
                        return;
                    }

                    $pressedButton = $form->getPressedSubmitElement();
                    if ($pressedButton && $pressedButton->getName() === 'remove') {
                        $this->deleteConfig($db);

                        Notification::success($this->translate('Source has successfully been deleted'));
                    } else {
                        $this->generateSource(
                            $form->getValue('notifications_url'),
                            $form->getValue('notifications_kubernetes_web_url')
                        );

                        Notification::success(
                            $this->translate('New configuration has successfully been stored')
                        );
                    }

                    $this->redirectNow('__REFRESH__');
                }
            )->handleRequest($this->getServerRequest());

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('notifications'));

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

    protected function deleteConfig(Connection $db): void
    {
        $db->delete('config',
            [
                $db->quoteIdentifier('key') . ' = ?' => self::NOTIFICATIONS_SOURCE_ID
            ]
        );
        $db->delete('config',
            [
                $db->quoteIdentifier('key') . ' = ?' => self::NOTIFICATIONS_URL
            ]
        );
        $db->delete('config',
            [
                $db->quoteIdentifier('key') . ' = ?' => self::NOTIFICATIONS_KUBERNETES_WEB_URL
            ]
        );
        $db->delete('config',
            [
                $db->quoteIdentifier('key') . ' = ?' => self::NOTIFICATIONS_USERNAME
            ]
        );
        $db->delete('config',
            [
                $db->quoteIdentifier('key') . ' = ?' => self::NOTIFICATIONS_PASSWORD
            ]
        );
    }

    protected function generateSource(string $url, $kubernetes_web_url): void
    {
        $sourceForm = new class (\Icinga\Module\Notifications\Common\Database::get()) extends SourceForm {
            public function hasBeenSubmitted()
            {
                return $this->hasBeenSent(); // Cheating :)
            }
        };

        $password = sha1(openssl_random_pseudo_bytes(16));

        $formData = [
            'listener_password'      => $password,
            'listener_password_dupe' => $password,
            'name'                   => self::DEFAULT_SOURCE_USER,
            'type'                   => 'kubernetes',
            'icinga2_insecure_tls'   => 'n',
        ];

        $configData = [
            'url'                => $url,
            'kubernetes_web_url' => $kubernetes_web_url,
            'password'           => $password,
        ];

        $sourceForm
            ->populate($formData)
            ->on(SourceForm::ON_SUCCESS, function (SourceForm $form) use ($configData) {
                try {
                    $form->addSource();
                } catch (Throwable $err) {
                    Logger::error(
                        'Failed to populate Icinga for Kubernetes notifications source: %s',
                        $err
                    );
                    Logger::debug($err->getTraceAsString());

                    return;
                }

                try {
                    $this->generateConfigForSource($configData);
                } catch (Throwable $err) {
                    Logger::error('Failed to insert Icinga for Kubernetes notifications source ID: %s', $err);
                    Logger::debug($err->getTraceAsString());
                }
            });

        // Again cheating to match the server request and our source form method.
        $orgRequestMethod = $_SERVER['REQUEST_METHOD'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = ServerRequest::fromGlobals();
        $_SERVER['REQUEST_METHOD'] = $orgRequestMethod;

        try {
            $sourceForm->ensureAssembled();
            $csrf = $sourceForm->getElement('CSRFToken');
            if (preg_match('/ value="([^"]+)/', $csrf->getAttributes()->render(), $matches)) {
                // CSRF token validation was changed in the meantime, so that it always triggers the validation,
                // even without a populated token, so we need to workaround it here.
                $csrf->setValue($matches[1]);
            }

            $sourceForm->handleRequest($request);
        } catch (Throwable $err) {
            $this->addError('kubernetes.source.error', time(), $err->getMessage());

            Logger::error($err);
            Logger::debug($err->getTraceAsString());
        }
    }

    protected function generateConfigForSource(array $config): void
    {
        $sourceId = \Icinga\Module\Notifications\Common\Database::get()->lastInsertid();
        $db = Database::connection();
        $this->upsertConfig($db,
            [
                $db->quoteIdentifier('key') => self::NOTIFICATIONS_SOURCE_ID,
                'value'                     => $sourceId,
            ]);
        $this->upsertConfig($db,
            [
                $db->quoteIdentifier('key') => self::NOTIFICATIONS_URL,
                'value'                     => $config['url'],
            ]);
        $this->upsertConfig($db,
            [
                $db->quoteIdentifier('key') => self::NOTIFICATIONS_KUBERNETES_WEB_URL,
                'value'                     => $config['kubernetes_web_url'],
            ]);
        $this->upsertConfig($db,
            [
                $db->quoteIdentifier('key') => self::NOTIFICATIONS_USERNAME,
                'value'                     => self::DEFAULT_SOURCE_USER,
            ]);
        $this->upsertConfig($db,
            [
                $db->quoteIdentifier('key') => self::NOTIFICATIONS_PASSWORD,
                'value'                     => $config['password'],
            ]
        );
    }

    protected function upsertConfig(Connection $db, array $data): void
    {
        $res = KConfig::on($db)->filter(Filter::equal('key', $data[$db->quoteIdentifier('key')]));
        if (isset($res->first()->value)) {
            $db->update('config',
                ['value' => $data['value']],
                [$db->quoteIdentifier('key') . ' = ?' => $data[$db->quoteIdentifier('key')]]
            );
        } else {
            $db->insert('config', $data);
        }
    }
}

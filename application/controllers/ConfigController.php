<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Application\Config;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Forms\DatabaseConfigForm;
use Icinga\Module\Kubernetes\Forms\NotificationsConfigForm;
use Icinga\Module\Kubernetes\Forms\PrometheusConfigForm;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tabs;
use ipl\Stdlib\Filter;

class ConfigController extends Controller
{
    protected bool $disableDefaultAutoRefresh = true;

    public function init(): void
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
                Filter::equal('key', 'notifications.username'),
                Filter::equal('key', 'notifications.password'),
                Filter::equal('key', 'notifications.source_id'),
                Filter::equal('key', 'notifications.url'),
                Filter::equal('key', 'notifications.kubernetes_web_url'),
                Filter::equal('key', 'notifications.locked')
            )
        );

        $data = [];

        foreach ($dbConfig as $pair) {
            switch ($pair->key) {
                case 'notifications.url':
                    $data['notifications_url'] = $pair->value;
                    break;
                case 'notifications.kubernetes_web_url':
                    $data['notifications_kubernetes_web_url'] = $pair->value;
                    break;
                case 'notifications.username':
                    $data['notifications_username'] = $pair->value;
                    break;
                case 'notifications.password':
                    $data['notifications_password'] = $pair->value;
                    break;
            }
        }

        $form = (new NotificationsConfigForm())
            ->populate($data)
            ->on(NotificationsConfigForm::ON_SUCCESS, function ($form) use ($db, $data) {
                if ($form->isLocked()) {
                    Notification::error($this->translate('Notifications configuration is locked'));
                    return;
                }

                if (isset($data['notifications_url'])) {
                    $db->update('config',
                        ['value' => $form->getValue('notifications_url')],
                        [$db->quoteIdentifier('key') . ' = ?' => 'notifications.url']
                    );
                } else {
                    $db->insert('config',
                        [
                            $db->quoteIdentifier('key') => 'notifications.url',
                            'value'                     => $form->getValue('notifications_url')
                        ]
                    );
                }

                if (isset($data['notifications_kubernetes_web_url'])) {
                    $db->update('config',
                        ['value' => $form->getValue('notifications_kubernetes_web_url')],
                        [$db->quoteIdentifier('key') . ' = ?' => 'notifications.kubernetes_web_url']
                    );
                } else {
                    $db->insert('config',
                        [
                            $db->quoteIdentifier('key') => 'notifications.kubernetes_web_url',
                            'value'                     => $form->getValue('notifications_kubernetes_web_url')
                        ]
                    );

                }

                if (isset($data['notifications_username'])) {
                    $db->update('config',
                        ['value' => $form->getValue('notifications_username')],
                        [$db->quoteIdentifier('key') . ' = ?' => 'notifications.username']
                    );
                } else {
                    $db->insert('config',
                        [
                            $db->quoteIdentifier('key') => 'notifications.username',
                            'value'                     => $form->getValue('notifications_username')
                        ]
                    );
                }

                if (isset($data['notifications_password'])) {
                    $db->update('config',
                        ['value' => $form->getValue('notifications_password')],
                        [$db->quoteIdentifier('key') . ' = ?' => 'notifications.password']
                    );
                } else {
                    $db->insert('config',
                        [
                            $db->quoteIdentifier('key') => 'notifications.password',
                            'value'                     => $form->getValue('notifications_password')
                        ]
                    );
                }


                Notification::success($this->translate('New configuration has successfully been stored'));
            })->handleRequest($this->getServerRequest());

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
}

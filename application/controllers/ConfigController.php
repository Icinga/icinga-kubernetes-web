<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Application\Config;
use Icinga\Module\Kubernetes\Forms\DatabaseConfigForm;
use Icinga\Module\Kubernetes\Forms\PrometheusConfigForm;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\Tabs;
use ipl\Stdlib\Filter;

class ConfigController extends Controller
{
    public const PROMETHEUS_URL = 'prometheus.url';

    public const PROMETHEUS_USERNAME = 'prometheus.username';

    public const PROMETHEUS_PASSWORD = 'prometheus.password';

    public function init()
    {
        $this->assertPermission('config/modules');

        parent::init();
    }

    public function databaseAction()
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

    public function prometheusAction()
    {
        $db = Database::connection();
        $dbConfig = KConfig::on($db)->filter(
            Filter::any(
                Filter::equal('key', self::PROMETHEUS_URL),
                Filter::equal('key', self::PROMETHEUS_USERNAME),
                Filter::equal('key', self::PROMETHEUS_PASSWORD)
            )
        );

        $data = [];

        foreach ($dbConfig as $pair) {
            switch ($pair->key) {
                case self::PROMETHEUS_URL:
                    $data['prometheus_url'] = $pair->value;
                    break;
                case self::PROMETHEUS_USERNAME:
                    $data['prometheus_username'] = $pair->value;
                    break;
                case self::PROMETHEUS_PASSWORD:
                    $data['prometheus_password'] = $pair->value;
                    break;
            }
        }

        $form = (new PrometheusConfigForm())
            ->populate($data)
            ->on(PrometheusConfigForm::ON_SUCCESS, function ($form) use ($db, $data) {
                if ($form->isLocked()) {
                    Notification::error($this->translate('Prometheus configuration is locked'));
                    return;
                }

                if (isset($data['prometheus_url'])) {
                    $db->update('config',
                        ['value' => $form->getValue('prometheus_url')],
                        [$db->quoteIdentifier('key') . ' = ?' => self::PROMETHEUS_URL]
                    );
                } else {
                    $db->insert('config',
                        [
                            $db->quoteIdentifier('key') => self::PROMETHEUS_URL,
                            'value'                     => $form->getValue('prometheus_url')
                        ]
                    );
                }

                if (isset($data['prometheus_username'])) {
                    $db->update('config',
                        ['value' => $form->getValue('prometheus_username')],
                        [$db->quoteIdentifier('key') . ' = ?' => self::PROMETHEUS_USERNAME]
                    );
                } else {
                    $db->insert('config',
                        [
                            $db->quoteIdentifier('key') => self::PROMETHEUS_USERNAME,
                            'value'                     => $form->getValue('prometheus_username')
                        ]
                    );
                }

                if (isset($data['prometheus_password'])) {
                    $db->update('config',
                        ['value' => $form->getValue('prometheus_password')],
                        [$db->quoteIdentifier('key') . ' = ?' => self::PROMETHEUS_PASSWORD]
                    );
                } else {
                    $db->insert('config',
                        [
                            $db->quoteIdentifier('key') => self::PROMETHEUS_PASSWORD,
                            'value'                     => $form->getValue('prometheus_password')
                        ]
                    );
                }

                $this->redirectNow('__REFRESH__');

                Notification::success($this->translate('New configuration has successfully been stored'));
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
        /** @var Tab $tab */
        foreach ($tabs->getTabs() as $tab) {
            $this->tabs->add($tab->getName(), $tab);
        }
    }
}

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
        $dbConfig = KConfig::on($db)->filter(Filter::equal('key', 'prometheus.url'))->first();

//        $config = Config::module('kubernetes');
        $form = (new PrometheusConfigForm())
            ->populate(['prometheus_url' => $dbConfig->value])
            ->on(PrometheusConfigForm::ON_SUCCESS, function ($form) use ($db, $dbConfig) {
                if ($form->isLocked()) {
                    Notification::error($this->translate('Prometheus configuration is locked'));
                    return;
                }

//                $config->setSection('prometheus', $form->getValues());
//                $config->saveIni();

                if ($dbConfig) {
                    $db->update('config',
                        ['value' => $form->getValue('prometheus_url')],
                        [$db->quoteIdentifier('key') . ' = ?' => 'prometheus.url']
                    );
                } else {
                    $db->insert('config',
                        [
                            $db->quoteIdentifier('key')   => 'prometheus.url',
                            'value' => $form->getValue('prometheus_url')
                        ]
                    );
                }


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

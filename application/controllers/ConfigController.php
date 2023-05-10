<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Application\Config;
use Icinga\Module\Kubernetes\Forms\DatabaseConfigForm;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\Tabs;
use ipl\Web\Compat\CompatController;

class ConfigController extends CompatController
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

                Notification::success(t('New configuration has successfully been stored'));
            })->handleRequest($this->getServerRequest());

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('database'));

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

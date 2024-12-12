<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\Database;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\I18n\Translation;

abstract class Dashboard extends HtmlDocument
{
    use Translation;

    /** @var string */
    protected $name;

    /** @var  Dashlet[] */
    protected $dashlets;

    protected $dashletNames;

    /**
     * @param string $name
     *
     * @return self
     */
    public static function loadByName(string $name)
    {
        $class = __NAMESPACE__ . '\\' . ucfirst($name) . 'Dashboard';
        $dashboard = new $class();
        $dashboard->name = $name;

        return $dashboard;
    }

    public static function exists($name)
    {
        return class_exists(__NAMESPACE__ . '\\' . ucfirst($name) . 'Dashboard');
    }

    public function render()
    {
        $this
            ->setSeparator("\n")
            ->add(Html::tag('h1', null, $this->getTitle()))
            ->add($this->renderDashlets());

        return parent::render();
    }

    public function renderDashlets()
    {
        $ul = Html::tag('ul', [
            'class' => 'main-actions',
            'data-base-target' => '_self'
        ]);

        foreach ($this->dashlets() as $dashlet) {
            $ul->add($dashlet);
        }

        return $ul;
    }

    abstract public function getTitle();

    public function dashlets()
    {
        if ($this->dashlets === null) {
            $this->loadDashlets();
        }

        return $this->dashlets;
    }

    public function loadDashlets()
    {
        $names = $this->getDashletNames();

        if (empty($names)) {
            $this->dashlets = array();
        } else {
            $this->dashlets = Dashlet::loadByNames(
                $this->dashletNames,
            );
        }
    }

    public function getDashletNames()
    {
        return $this->dashletNames;
    }
}

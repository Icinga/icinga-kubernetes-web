<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Link;

abstract class Dashlet extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'li';

    /**
     * @param $name
     *
     * @return mixed
     */
    public static function loadByName($name)
    {
        /** @var Dashlet */
        $class = __NAMESPACE__ . '\\' . $name . 'Dashlet';

        return new $class();
    }

    public static function loadByNames(array $names)
    {
        $dashlets = [];
        foreach ($names as $name) {
            $dashlet = static::loadByName($name);

            $dashlets[] = $dashlet;
        }

        return $dashlets;
    }

    public function getIconName()
    {
        return $this->icon;
    }

    abstract public function getTitle();

    abstract public function getUrl();

    protected function assemble()
    {
        $this->addHtml((
            new Link(
                [
                    $this->getTitle(),
                    new HtmlElement(
                        'i',
                        new Attributes(['class' => $this->getIconName()])
                    ),
                    new HtmlElement(
                        'p',
                        null,
                        new Text($this->getSummary())
                    )
                ],
                $this->getUrl(),
            )
        ));
    }
}

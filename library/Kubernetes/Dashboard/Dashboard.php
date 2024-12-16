<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Dashboard;

use Icinga\Module\Kubernetes\Common\BeforeAssemble;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;

abstract class Dashboard extends BaseHtmlElement
{
    use BeforeAssemble;
    use Translation;

    protected $tag = 'ul';

    abstract protected function getTitle(): string;

    protected function beforeAssemble(): void
    {
        $this->setWrapper(new HtmlElement(
            'section',
            new Attributes(['class' => 'kubernetes-dashboard']),
            new HtmlElement('h2', null, new Text($this->getTitle()))
        ));
    }
}

<?php

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Attributes;
use ipl\Html\Text;

class NavigationList extends BaseHtmlElement
{
    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'navigation-list'];
    protected $listItems = [];

    public function __construct(array $listItems)
    {
        $this->listItems = $listItems;
    }

    protected function assemble()
    {
        foreach ($this->listItems as $listItem) {
            $this->addHtml(
                new HtmlElement(
                    'li',
                    null,
                    new HtmlElement(
                        'a',
                        new Attributes(['href' => $listItem['href']]),
                        new Text($listItem['text'])
                    )
                )
            );
        }
    }
}

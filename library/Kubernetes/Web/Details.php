<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;

class Details extends BaseHtmlElement
{
    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'resource-details'];

    protected $details;

    public function __construct(iterable $details)
    {
        $this->details = $details;
    }

    protected function assemble()
    {
        $this->addHtml(new HtmlElement('h2', null, new Text(t('Details'))));

        foreach ($this->details as $key => $value) {
            $this->addHtml(new HorizontalKeyValue($key, $value));
        }
    }
}

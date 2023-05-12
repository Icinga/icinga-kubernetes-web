<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\Deployment;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class DeploymentDetail extends BaseHtmlElement
{
    /** @var Deployment */
    private $item;

    protected $tag = 'ul';

    public function __construct($item)
    {
        $this->item = $item;
    }

    protected function assemble()
    {
        foreach ($this->item->getColumns() as $col) {
            $this->add(new HtmlElement('li', new Attributes(['value' => $col, 'class' => 'deployment-detail-item'])));
        }
    }

}
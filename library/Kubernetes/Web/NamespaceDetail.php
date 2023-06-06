<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\NamespaceModel;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class NamespaceDetail extends BaseHtmlElement
{
    /** @var NamespaceModel */
    protected $namespace;

    protected $defaultAttributes = [
        'class' => 'namespace-detail',
    ];

    protected $tag = 'div';

    public function __construct(NamespaceModel $namespace)
    {
        $this->namespace = $namespace;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details([
                t('Name')                 => $this->namespace->name,
                t('UID')                  => $this->namespace->uid,
                t('Resource Version')     => $this->namespace->resource_version,
                t('Phase')                => $this->namespace->phase,
                t('Created')              => $this->namespace->created->format('Y-m-d H:i:s')
            ]),
            new Labels($this->namespace->label)
        );
    }
}

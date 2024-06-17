<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\NamespaceModel;
use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;

class NamespaceDetail extends BaseHtmlElement
{
    use Translation;

    /** @var NamespaceModel */
    protected $namespace;

    protected $tag = 'div';

    public function __construct(NamespaceModel $namespace)
    {
        $this->namespace = $namespace;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details([
                $this->translate('Name')             => $this->namespace->name,
                $this->translate('UID')              => $this->namespace->uid,
                $this->translate('Resource Version') => $this->namespace->resource_version,
                $this->translate('Created')          => $this->namespace->created->format('Y-m-d H:i:s'),
                $this->translate('Phase')            => $this->namespace->phase
            ]),
            new Labels($this->namespace->label),
            new Annotations($this->namespace->annotation),
            new Yaml($this->namespace->yaml)
        );
    }
}

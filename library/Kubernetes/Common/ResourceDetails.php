<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use AppendIterator;
use ArrayIterator;
use Generator;
use Icinga\Module\Kubernetes\Web\KIcon;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use IteratorAggregate;
use Traversable;

class ResourceDetails implements IteratorAggregate
{
    use Translation;

    protected ?iterable $details;

    protected $resource;

    public function __construct($resource, iterable $details = null)
    {
        $this->resource = $resource;
        $this->details = $details;
    }

    public function getIterator(): Traversable
    {
        $iterator = new AppendIterator();
        $iterator->append(new ArrayIterator([
            $this->translate('Name')             => $this->resource->name,
            $this->translate('Namespace')        => new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                new KIcon('namespace'),
                new Text($this->resource->namespace)
            ),
            $this->translate('UID')              => $this->resource->uid,
            $this->translate('Resource Version') => $this->resource->resource_version,
            $this->translate('Created')          => $this->resource->created->format('Y-m-d H:i:s')
        ]));

        if ($this->details !== null) {
            $iterator->append($this->yieldDetails());
        }

        return $iterator;
    }

    protected function yieldDetails(): Generator
    {
        foreach ($this->details as $k => $v) {
            yield $k => $v;
        }
    }
}

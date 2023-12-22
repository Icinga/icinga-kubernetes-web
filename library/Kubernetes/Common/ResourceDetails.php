<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

use AppendIterator;
use ArrayIterator;
use Generator;
use IteratorAggregate;
use Traversable;

class ResourceDetails implements IteratorAggregate
{
    protected $resource;

    /** @var ?iterable */
    protected $details;

    public function __construct($resource, iterable $details = null)
    {
        $this->resource = $resource;
        $this->details = $details;
    }

    public function getIterator(): Traversable
    {
        $iterator = new AppendIterator();
        $iterator->append(new ArrayIterator([
            t('Name')             => $this->resource->name,
            t('Namespace')        => $this->resource->namespace,
            t('UID')              => $this->resource->uid,
            t('Resource Version') => $this->resource->resource_version,
            t('Created')          => $this->resource->created->format('Y-m-d H:i:s')
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

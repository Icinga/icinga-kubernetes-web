<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes;

use Generator;

/**
 * Turn any iterable into a generator
 *
 * @param iterable $iterable
 *
 * @return Generator
 */
function yield_iterable(iterable $iterable): Generator
{
    foreach ($iterable as $k => $v) {
        yield $k => $v;
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\TBD;

use ipl\Sql\Cursor;
use Traversable;

class ObjectSuggestionsCursor extends Cursor
{
    public function getIterator(): Traversable
    {
        foreach (parent::getIterator() as $key => $value) {
            // TODO(lippserd): This is a quick and dirty fix for PostgreSQL binary datatypes for which PDO returns
            // PHP resources that would cause exceptions since resources are not a valid type for attribute values.
            // We need to do it this way as the suggestion implementation bypasses ORM behaviors here and there.
            if (is_resource($value)) {
                $value = stream_get_contents($value);
            }

            yield $key => $value;
        }
    }
}

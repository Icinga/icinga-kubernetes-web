<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model\Behavior;

use ipl\Orm\Contract\QueryAwareBehavior;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Sql\Select;

/**
 * Hide soft-deleted resources from normal Kubernetes Web queries.
 */
class HideDeleted implements QueryAwareBehavior
{
    protected Model $model;

    protected ?string $relation;

    public function __construct(Model $model, ?string $relation = null)
    {
        $this->model = $model;
        $this->relation = $relation;
    }

    public function setQuery(Query $query): static
    {
        if ($query->getModel() !== $this->model) {
            return $this;
        }

        if ($this->relation !== null) {
            $query->utilize($this->relation);
        }

        $query->on(Query::ON_SELECT_ASSEMBLED, function (Select $select) use ($query) {
            $resolver = $query->getResolver();
            $model = $this->model;

            if ($this->relation !== null) {
                $model = $resolver->getRelations($model)->get($this->relation)->getTarget();
            }

            $column = $resolver->qualifyColumn('deleted', $resolver->getAlias($model));
            $select->where("$column IS NULL");
        });

        return $this;
    }
}

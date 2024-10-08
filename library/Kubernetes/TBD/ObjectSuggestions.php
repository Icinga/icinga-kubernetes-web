<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\TBD;

use Generator;
use Icinga\Module\Kubernetes\Common\Database;
use ipl\I18n\Translation;
use ipl\Orm\Exception\InvalidColumnException;
use ipl\Orm\Model;
use ipl\Orm\Relation;
use ipl\Orm\Relation\HasOne;
use ipl\Orm\Resolver;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Seq;
use ipl\Web\Control\SearchBar\SearchException;
use ipl\Web\Control\SearchBar\Suggestions;
use LogicException;
use PDO;
use Traversable;

class ObjectSuggestions extends Suggestions
{
    use Translation;

    protected Model $model;

    protected function createQuickSearchFilter($searchTerm): Filter\Chain
    {
        $model = $this->getModel();
        $resolver = $model::on(Database::connection())->getResolver();

        $quickFilter = Filter::any();
        foreach ($model->getSearchColumns() as $column) {
            $where = Filter::like($resolver->qualifyColumn($column, $model->getTableName()), $searchTerm);
            $where->metaData()->set('columnLabel', $resolver->getColumnDefinition($where->getColumn())->getLabel());
            $quickFilter->add($where);
        }

        return $quickFilter;
    }

    protected function fetchColumnSuggestions($searchTerm): Generator
    {
        $model = $this->getModel();
        $query = $model::on(Database::connection());

        // Ordinary columns first
        foreach (self::collectFilterColumns($model, $query->getResolver()) as $columnName => $columnMeta) {
            yield $columnName => $columnMeta;
        }
    }

    protected function fetchValueSuggestions($column, $searchTerm, Filter\Chain $searchFilter): ObjectSuggestionsCursor
    {
        $model = $this->getModel();
        $query = $model::on(Database::connection());
        $query->limit(static::DEFAULT_LIMIT);

        if (str_contains($column, ' ')) {
            // $column may be a label
            list($path, $_) = Seq::find(
                self::collectFilterColumns($query->getModel(), $query->getResolver()),
                $column,
                false
            );

            if ($path !== null) {
                $column = $path;
            }
        }

        $columnPath = $query->getResolver()->qualifyPath($column, $model->getTableName());

        $inputFilter = Filter::like($columnPath, $searchTerm);
        $query->columns($columnPath);
        $query->orderBy($columnPath);

        if ($searchFilter instanceof Filter\None) {
            $query->filter($inputFilter);
        } elseif ($searchFilter instanceof Filter\All) {
            $searchFilter->add($inputFilter);

            // There may be columns part of $searchFilter which target the base table. These must be
            // optimized, otherwise they influence what we'll suggest to the user. (i.e. less)
            // The $inputFilter on the other hand must not be optimized, which it wouldn't, but since
            // we force optimization on its parent chain, we have to negate that.
            $searchFilter->metaData()->set('forceOptimization', true);
            $inputFilter->metaData()->set('forceOptimization', false);
        } else {
            $searchFilter = $inputFilter;
        }

        $query->filter($searchFilter);

        try {
            return (new ObjectSuggestionsCursor($query->getDb(), $query->assembleSelect()->distinct()))
                ->setFetchMode(PDO::FETCH_COLUMN);
        } catch (InvalidColumnException $e) {
            throw new SearchException(sprintf($this->translate('"%s" is not a valid column'), $e->getColumn()));
        }
    }

    protected function matchSuggestion($path, $label, $searchTerm): bool
    {
        if (preg_match('/[_.](id)$/', $path)) {
            // Only suggest exotic columns if the user knows about them
            $trimmedSearch = trim($searchTerm, ' *');

            return str_ends_with($path, $trimmedSearch);
        }

        return parent::matchSuggestion($path, $label, $searchTerm);
    }

    protected function shouldShowRelationFor(string $column): bool
    {
        $tableName = $this->getModel()->getTableName();
        $columnPath = explode('.', $column);

        if (count($columnPath) > 2) {
            return true;
        }

        return $columnPath[0] !== $tableName;
    }

    /**
     * Get the model to show suggestions for
     *
     * @return Model
     */
    public function getModel(): Model
    {
        if ($this->model === null) {
            throw new LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->model;
    }

    /**
     * Collect all columns of this model and its relations that can be used for filtering
     *
     * @param Model $model
     * @param Resolver $resolver
     *
     * @return Traversable
     */
    public static function collectFilterColumns(Model $model, Resolver $resolver): Traversable
    {
        $models = [$model->getTableName() => $model];
        self::collectRelations($resolver, $model, $models, []);

        foreach ($models as $path => $targetModel) {
            foreach ($resolver->getColumnDefinitions($targetModel) as $columnName => $definition) {
                yield $path . '.' . $columnName => $definition->getLabel();
            }
        }
    }

    /**
     * Collect all direct relations of the given model
     * A direct relation is either a direct descendant of the model
     * or a descendant of such related in a to-one cardinality.
     *
     * @param Resolver $resolver
     * @param Model $subject
     * @param array $models
     * @param array $path
     */
    protected static function collectRelations(Resolver $resolver, Model $subject, array &$models, array $path): void
    {
        foreach ($resolver->getRelations($subject) as $name => $relation) {
            /** @var Relation $relation */
            $isHasOne = $relation instanceof HasOne;
            if (empty($path)) {
                $relationPath = [$name];
                if ($isHasOne) {
                    array_unshift($relationPath, $subject->getTableName());
                }

                $relationPath = array_merge($path, $relationPath);
                $models[join('.', $relationPath)] = $relation->getTarget();
                self::collectRelations($resolver, $relation->getTarget(), $models, $relationPath);
            }
        }
    }

    /**
     * Set the model to show suggestions for
     *
     * @param string|Model $model
     *
     * @return $this
     */
    public function setModel(string|Model $model): static
    {
        if (is_string($model)) {
            $model = new $model();
        }

        $this->model = $model;

        return $this;
    }
}

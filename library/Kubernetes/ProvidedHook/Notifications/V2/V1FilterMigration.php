<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\ProvidedHook\Notifications\V2;

use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Notifications\Hook\V2\V1FilterMigrationHook;
use InvalidArgumentException;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Chain;
use ipl\Stdlib\Filter\Condition;
use ipl\Stdlib\Filter\Rule;
use ipl\Stdlib\Str;
use ipl\Web\Filter\ParseException;
use ipl\Web\Filter\Parser;
use JsonException;

/**
 * Migrate rule filters serialized for Icinga Notifications V1 filters
 */
class V1FilterMigration implements V1FilterMigrationHook
{
    /** @var string[] Kinds a V1 filter could target */
    private const KINDS = ['daemon_set', 'deployment', 'node', 'pod', 'replica_set', 'stateful_set'];

    /** @var array<string, string[]> Tables of the possible direct owners of a kind, keyed by its table */
    private const OWNER_TABLES = [
        'pod'         => ['daemon_set', 'job', 'replica_set', 'stateful_set'],
        'replica_set' => ['deployment']
    ];

    public function getSourceType(): string
    {
        return KConfig::DEFAULT_NOTIFICATIONS_TYPE;
    }

    public function migrateFilter(string $v1Filter): Chain
    {
        try {
            $data = json_decode($v1Filter, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON: ' . $e->getMessage(), previous: $e);
        }

        if (! is_array($data) || ($data['version'] ?? null) !== 1 || ! is_string($data['kind'] ?? null)) {
            throw new InvalidArgumentException('Invalid v1 filter format');
        }

        $table = Factory::createModel($data['kind'])?->getTableName();
        if (! in_array($table, self::KINDS, true)) {
            throw new InvalidArgumentException(sprintf('Unknown v1 filter kind "%s"', $data['kind']));
        }

        try {
            $filter = (new Parser($data['filter'] ?? ''))->parse();
        } catch (ParseException $e) {
            throw new InvalidArgumentException('Invalid filter: ' . $e->getMessage(), previous: $e);
        }

        if (! $filter instanceof Filter\All) {
            $filter = Filter::all($filter);
        }

        $this->migrateChain($filter, $table);

        return Filter::all(Filter::equal('object.kind', $table), ...$filter);
    }

    /**
     * Migrate the given chain and its children in place
     *
     * @param Chain $chain
     * @param string $table
     *
     * @return void
     */
    private function migrateChain(Chain $chain, string $table): void
    {
        $this->migrateLabels($chain);

        foreach (iterator_to_array($chain) as $rule) {
            if ($rule instanceof Chain) {
                $this->migrateChain($rule, $table);
            } else {
                /** @var Condition $rule */
                $migrated = $this->migrateCondition($rule, $table);
                if ($migrated !== $rule) {
                    $chain->replace($rule, $migrated);
                }
            }
        }
    }

    /**
     * Migrate the label conditions which are direct children of the given chain
     *
     * Labels were a to-many relation with the columns `name` and `value`. Conditions on both in the same
     * AND chain targeted the same label, so `label.name=X&label.value=Y` becomes `object.labels.X=Y`.
     *
     * @param Chain $chain
     *
     * @return void
     */
    private function migrateLabels(Chain $chain): void
    {
        $names = [];
        $values = [];
        foreach ($chain as $rule) {
            if (! $rule instanceof Condition) {
                continue;
            }

            $column = $rule->getColumn();
            if ($column === 'label.name') {
                $names[] = $rule;
            } elseif ($column === 'label.value') {
                $values[] = $rule;
            }
        }

        if (empty($values)) {
            foreach ($names as $name) {
                if ($name instanceof Filter\Equal && is_string($name->getValue())) {
                    $chain->replace($name, Filter::like('object.labels.' . $name->getValue(), '*'));
                }
            }
        } elseif (
            $chain instanceof Filter\All
            && count($names) === 1
            && count($values) === 1
            && $names[0] instanceof Filter\Equal
            && $values[0] instanceof Filter\Equal
            && is_string($names[0]->getValue())
        ) {
            $chain->replace(
                $names[0],
                Filter::equal('object.labels.' . $names[0]->getValue(), $values[0]->getValue())
            );
            $chain->remove($values[0]);
        }
    }

    /**
     * Migrate the given condition if it has an equivalent
     *
     * Owners were relations of their own, joined through the owner table of the kind. They are part of
     * `owners` now, so their conditions are rewritten.
     *
     * @param Condition $condition
     * @param string $table
     *
     * @return Rule The migrated condition or its replacement
     */
    private function migrateCondition(Condition $condition, string $table): Rule
    {
        $column = $this->stripTable($condition->getColumn(), $table);

        [$relation, $relationColumn] = Str::symmetricSplit($column, '.', 2);
        if (
            in_array($relation, self::OWNER_TABLES[$table] ?? [], true)
            && ! $condition instanceof Filter\Unequal
            && ! $condition instanceof Filter\Unlike
        ) {
            $ownerColumn = match ($relationColumn) {
                'name'      => 'owner.name',
                'namespace' => 'object.namespace',
                default     => null
            };

            if ($ownerColumn !== null) {
                return Filter::all(Filter::equal('owner.kind', $relation), $condition->setColumn($ownerColumn));
            }
        }

        $column = match ($column) {
            'name'                         => 'object.name',
            'namespace'                    => 'object.namespace',
            'cluster.name'                 => 'cluster.name',
            'cluster_uuid', 'cluster.uuid' => 'cluster.uuid',
            'owner.name'                   => 'owner.name',
            'owner.kind'                   => 'owner.kind',
            'node_name', 'node.name'       => 'node.name',
            default                        => null
        };

        if ($column !== null) {
            $condition->setColumn($column);
        }

        return $condition;
    }

    /**
     * Strip the table of the targeted kind from the given column, if present
     *
     * @param string $column
     * @param string $table
     *
     * @return string
     */
    private function stripTable(string $column, string $table): string
    {
        return str_starts_with($column, $table . '.') ? substr($column, strlen($table) + 1) : $column;
    }
}

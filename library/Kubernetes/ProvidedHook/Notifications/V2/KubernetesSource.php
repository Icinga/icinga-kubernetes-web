<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\ProvidedHook\Notifications\V2;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Model\Label;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Notifications\Hook\V2\SourceHook;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Orm\Query;
use ipl\Sql\Sql;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Chain;
use ipl\Stdlib\Filter\Condition;
use ipl\Web\Control\SearchBar\SearchException;
use ipl\Web\Widget\Icon;
use Ramsey\Uuid\Uuid;
use Traversable;

class KubernetesSource implements SourceHook
{
    use Translation;

    private const LABEL_PATTERN = '/^object\.labels\.(.+)$/';

    /** @var string[] Kinds an event object can be of */
    private const KINDS = ['daemon_set', 'deployment', 'node', 'pod', 'replica_set', 'stateful_set'];

    /** @var string[] Kinds that can own an event object */
    private const OWNER_KINDS = ['daemon_set', 'deployment', 'job', 'replica_set', 'stateful_set'];

    private array $allowedColumns;

    public function __construct()
    {
        $this->allowedColumns = [
            'object.kind'      => $this->translate('Kind'),
            'object.name'      => $this->translate('Name'),
            'object.namespace' => $this->translate('Namespace'),
            'cluster.name'     => $this->translate('Cluster Name'),
            'cluster.uuid'     => $this->translate('Cluster UUID'),
            'owner.name'       => $this->translate('Owner Name'),
            'owner.kind'       => $this->translate('Owner Kind'),
            'node.name'        => $this->translate('Node Name'),
        ];
    }

    public function getSourceLabel(): string
    {
        return KConfig::DEFAULT_NOTIFICATIONS_NAME;
    }

    public function getSourceIcon(): Icon
    {
        return new Icon('globe');
    }

    public function assertValidCondition(Condition $condition): void
    {
        $column = $condition->getColumn();
        if (isset($this->allowedColumns[$column])) {
            return;
        }

        if (preg_match(static::LABEL_PATTERN, $column, $m)) {
            if (
                $this->labelQuery()
                    ?->columns(['name'])
                    ->filter(Filter::equal('name', $m[1]))
                    ->limit(1)
                    ->first() !== null
            ) {
                return;
            } else {
                throw new SearchException($this->translate('Label not found'));
            }
        }

        throw new SearchException($this->translate('Is not a valid Column'));
    }

    public function enrichCondition(Condition $condition): void
    {
        $column = $condition->getColumn();
        if (isset($this->allowedColumns[$column])) {
            $condition->metaData()->set('columnLabel', $this->allowedColumns[$column]);
        } elseif (preg_match(static::LABEL_PATTERN, $column, $m)) {
            $condition->metaData()->set('columnLabel', $m[1]);
        }
    }

    public function getJsonPaths(string ...$columns): array
    {
        $jsonPaths = [];
        foreach ($columns as $column) {
            if (! isset($jsonPaths[$column])) {
                if (isset($this->allowedColumns[$column])) {
                    $jsonPaths[$column] = match ($column) {
                        'owner.name' => ['owners[*].name'],
                        'owner.kind' => ['owners[*].kind'],
                        default      => [$column],
                    };
                } elseif (preg_match(static::LABEL_PATTERN, $column, $m)) {
                    $jsonPaths[$column] = ['object.labels["' . $m[1] . '"]'];
                }
            }
        }

        return $jsonPaths;
    }

    public function getEventTypes(): array
    {
        return [];
    }

    public function getValueSuggestions(string $column, string $searchTerm, Chain $searchFilter): Traversable
    {
        $kinds = match ($column) {
            'object.kind' => self::KINDS,
            'owner.kind'  => self::OWNER_KINDS,
            default       => null,
        };

        if ($kinds !== null) {
            foreach ($kinds as $kind) {
                if (fnmatch($searchTerm, $kind)) {
                    yield ['search' => $kind, 'label' => $kind];
                }
            }

            return;
        }

        $db = Database::connection();

        if ($column === 'cluster.uuid') {
            foreach (Cluster::on($db)->columns('uuid') as $cluster) {
                $uuid = Uuid::fromBytes($cluster->uuid)->toString();
                if (fnmatch($searchTerm, $uuid)) {
                    yield ['search' => $uuid, 'label' => $uuid];
                }
            }

            return;
        }

        if (isset($this->allowedColumns[$column])) {
            [$queries, $valueColumn] = match ($column) {
                'object.name'      => [
                    array_map(fn(string $kind) => $this->restrictedQuery($kind), self::KINDS),
                    'name'
                ],
                'object.namespace' => [[$this->restrictedQuery('namespace')], 'name'],
                'node.name'        => [[$this->restrictedQuery('node')], 'name'],
                'owner.name'       => [
                    array_map(fn(string $kind) => $this->restrictedQuery($kind), self::OWNER_KINDS),
                    'name'
                ],
                'cluster.name'     => [[Cluster::on($db)], 'name'],
                default            => [[], null],
            };
        } elseif (preg_match(static::LABEL_PATTERN, $column, $m)) {
            $queries = [$this->labelQuery()?->filter(Filter::equal('name', $m[1]))];
            $valueColumn = 'value';
        } else {
            return;
        }

        $count = 0;
        foreach ($queries as $query) {
            if ($query === null) {
                // The user may not list this kind
                continue;
            }

            $query
                ->columns([$valueColumn])
                ->filter(Filter::like($valueColumn, $searchTerm))
                ->orderBy($valueColumn)
                ->limit(50 - $count);
            $query->getSelectBase()->distinct();

            foreach ($query as $value) {
                yield ['search' => $value->$valueColumn, 'label' => $value->$valueColumn];
                ++$count;
                if ($count >= 50) {
                    return;
                }
            }
        }
    }

    public function getColumnSuggestions(string $searchTerm): Traversable
    {
        foreach ($this->allowedColumns as $search => $label) {
            if (fnmatch($searchTerm, $label, FNM_CASEFOLD) || fnmatch($searchTerm, $search, FNM_CASEFOLD)) {
                yield ['search' => $search, 'label' => $label, 'group' => $this->translate('Columns')];
            }
        }

        $query = $this->labelQuery();
        if ($query === null) {
            return;
        }

        $query
            ->columns(['name'])
            ->filter(Filter::like('name', $searchTerm))
            ->orderBy('name')
            ->limit(50);
        $query->getSelectBase()->distinct();

        foreach ($query as $row) {
            yield [
                'search' => 'object.labels.' . $row->name,
                'label'  => $row->name,
                'group'  => $this->translate('Labels')
            ];
        }
    }

    public function createObjectLink(array $idTags): ?ValidHtml
    {
        if (! isset($idTags['resource']) || ! isset($idTags['uuid'])) {
            return null;
        }

        $object = $this->restrictedQuery($idTags['resource'])
            ?->filter(Filter::equal('uuid', $idTags['uuid']))
            ->first();

        if ($object === null) {
            return null;
        }

        return (new ResourceList([$object]))->setViewMode(ViewMode::Minimal);
    }

    /**
     * Query the resources of the given kind the user is allowed to see
     *
     * @param string $kind
     *
     * @return ?Query Null if the user may not list resources of the given kind
     */
    private function restrictedQuery(string $kind): ?Query
    {
        $kind = Factory::canonicalizeKind($kind);
        $auth = Auth::getInstance();
        if (! isset(Auth::PERMISSIONS[$kind]) || ! $auth->canList($kind)) {
            return null;
        }

        return $auth->withRestrictions(Auth::PERMISSIONS[$kind], Factory::fetchResource($kind));
    }

    /**
     * Query the labels of the resources the user is allowed to see
     *
     * The query ORs a `label.resource_uuid IN (...)` subquery per kind the user may list, each selecting the uuids
     * of that kind's resources with the user's restrictions applied.
     *
     * @return ?Query Null if the user may not list any kind
     */
    private function labelQuery(): ?Query
    {
        $resourceUuids = [];
        foreach (self::KINDS as $kind) {
            $resources = $this->restrictedQuery($kind);
            if ($resources !== null) {
                $resourceUuids[] = [
                    Sql::ALL,
                    ['label.resource_uuid IN ?' => $resources->columns(['uuid'])->assembleSelect()->resetOrderBy()]
                ];
            }
        }

        if (empty($resourceUuids)) {
            return null;
        }

        $query = Label::on(Database::connection());
        $query->getSelectBase()->where($resourceUuids, Sql::ANY);

        return $query;
    }
}

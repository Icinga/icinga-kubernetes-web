<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Kubernetes\ProvidedHook\Notifications\V2;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Model\DaemonSetOwner;
use Icinga\Module\Kubernetes\Model\DeploymentOwner;
use Icinga\Module\Kubernetes\Model\Label;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Model\PodOwner;
use Icinga\Module\Kubernetes\Model\ReplicaSetOwner;
use Icinga\Module\Kubernetes\Model\StatefulSetOwner;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Notifications\Hook\V2\SourceHook;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
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
                Label::on(Database::connection())
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
                        default => [$column]
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
        $kinds = ['daemon_set', 'deployment', 'node', 'pod', 'replica_set', 'stateful_set'];

        if ($column === 'object.kind') {
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

        if ($column === 'owner.name' || $column === 'owner.kind') {
            $ownerQueries = [
                PodOwner::on($db),
                ReplicaSetOwner::on($db),
                DaemonSetOwner::on($db),
                DeploymentOwner::on($db),
                StatefulSetOwner::on($db),
            ];
        }

        if (isset($this->allowedColumns[$column])) {
            [$queries, $valueColumn] = match ($column) {
                'object.name' => [array_map(fn(string $kind) => Factory::fetchResource($kind, $db), $kinds), 'name'],
                'object.namespace' => [[NamespaceModel::on($db)], 'name'],
                'cluster.name' => [[Cluster::on($db)], 'name'],
                'node.name' => [[Factory::fetchResource('node', $db)], 'name'],
                'owner.name' => [$ownerQueries, 'name'],
                'owner.kind' => [$ownerQueries, 'kind'],
                default => [[], null]
            };
        } elseif (preg_match(static::LABEL_PATTERN, $column, $m)) {
            [$queries, $valueColumn] = [[Label::on($db)->filter(Filter::equal('name', $m[1]))], 'value'];
        } else {
            return;
        }

        $count = 0;
        foreach ($queries as $query) {
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
            yield ['search' => $search, 'label' => $label, 'group' => $this->translate('Columns')];
        }

        $query = Label::on(Database::connection())
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

        $object = Factory::fetchResource($idTags['resource'])
            ->filter(Filter::equal('uuid', $idTags['uuid']))
            ->first();

        return (new ResourceList([$object]))->setViewMode(ViewMode::Minimal);
    }
}

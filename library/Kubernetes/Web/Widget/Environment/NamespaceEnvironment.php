<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\Environment;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodPvc;
use Icinga\Module\Kubernetes\Model\PodVolume;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\Secret;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Web\Widget\ItemCountIndicator;
use Icinga\Module\Kubernetes\Web\Widget\KIcon;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Url;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use Ramsey\Uuid\Uuid;
use SplObjectStorage;

/**
 * Tree of all workloads in a namespace, from ingresses and services down to pods
 *
 * Edges follow ingress backends (Ingress → Service), the workloads owning a service's pods (Service → Deployment etc.)
 * and owner references (Deployment → ReplicaSet → Pod, CronJob → Job → Pod, StatefulSet/DaemonSet → Pod).
 * Objects without a visible parent become roots.
 */
class NamespaceEnvironment implements ValidHtml
{
    use Translation;

    /** @var int Children shown per parent, the rest collapse into a summary link */
    protected const LIMIT = 3;

    /**
     * @var array<string, int> Column of each kind, so objects of the same kind line up below each other
     *
     * Kinds with the same role share a column: top-level workloads, then the controllers they create, then pods.
     * Stateful set and daemon set pods skip the controller column.
     */
    protected const COLUMNS = [
        'ingress'     => 1,
        'service'     => 2,
        'deployment'  => 3,
        'cronjob'     => 3,
        'statefulset' => 3,
        'daemonset'   => 3,
        'replicaset'  => 4,
        'job'         => 4,
        'pod'         => 5,
        // What pods use: claims, which are bound to volumes, config maps and secrets.
        'persistentvolumeclaim' => 6,
        'configmap'             => 6,
        'secret'                => 6,
        'persistentvolume'      => 7
    ];

    /** @var array<int, int> Columns of {@see COLUMNS} with nodes drawn, mapped to their position starting at 0 */
    protected array $columns = [];

    /** @var SplObjectStorage<HtmlElement, Model> Model of each node, for the summary of collapsed children */
    protected SplObjectStorage $models;

    /**
     * Create the tree of the given namespace
     *
     * @param NamespaceModel $namespace
     * @param ?Model $focus Only show this object's context: its ancestors and descendants
     */
    public function __construct(protected NamespaceModel $namespace, protected ?Model $focus = null)
    {
        $this->models = new SplObjectStorage();
    }

    /**
     * Create the tree of the given object's context: its ancestors and descendants
     *
     * @param Model $object
     *
     * @return ValidHtml
     */
    public static function forObject(Model $object): ValidHtml
    {
        $db = Database::connection();
        $clusterUuid = (string) Uuid::fromBytes($object->cluster_uuid);

        if ($object instanceof PersistentVolume) {
            // Volumes aren't namespaced, but their claim is.
            $namespace = PersistentVolumeClaim::on($db)
                ->columns(['namespace'])
                ->filter(Filter::all(
                    Filter::equal('cluster_uuid', $clusterUuid),
                    Filter::equal('volume_name', $object->name)
                ))
                ->first()
                ?->namespace;
        } else {
            $namespace = $object->namespace;
        }

        $namespace = $namespace === null ? null : NamespaceModel::on($db)
            ->filter(Filter::all(Filter::equal('cluster_uuid', $clusterUuid), Filter::equal('name', $namespace)))
            ->first();

        if ($namespace === null) {
            return new EmptyState(t('No items found.'));
        }

        return new static($namespace, $object);
    }

    public function render(): ValidHtml
    {
        $stateful = ['icinga_state'];
        $owned = ['icinga_state', 'owner.owner_uuid'];

        $pods = static::groupByOwner($this->fetch(Auth::SHOW_PODS, Pod::class, $owned));
        // Newest active replica sets first, so old revisions scaled to zero end up in the summary.
        $replicaSets = static::groupByOwner($this->fetch(
            Auth::SHOW_REPLICA_SETS,
            ReplicaSet::class,
            $owned,
            ['actual_replicas' => 'desc', 'created' => 'desc']
        ));
        $jobs = static::groupByOwner(
            $this->fetch(Auth::SHOW_JOBS, Job::class, $owned, ['created' => 'desc'])
        );
        $ingresses = $this->fetch(Auth::SHOW_INGRESSES, Ingress::class);
        $deployments = $this->fetch(Auth::SHOW_DEPLOYMENTS, Deployment::class, $stateful);
        $statefulSets = $this->fetch(Auth::SHOW_STATEFUL_SETS, StatefulSet::class, $stateful);
        $daemonSets = $this->fetch(Auth::SHOW_DAEMON_SETS, DaemonSet::class, $stateful);
        $cronJobs = $this->fetch(Auth::SHOW_CRON_JOBS, CronJob::class);

        $services = [];
        foreach ($this->fetch(Auth::SHOW_SERVICES, Service::class) as $service) {
            $services[$service->name] = $service;
        }

        $pvcs = [];
        $pvcList = $this->fetch(Auth::SHOW_PERSISTENT_VOLUME_CLAIMS, PersistentVolumeClaim::class, ['volume_name']);
        foreach ($pvcList as $pvc) {
            $pvcs[$pvc->name] = $pvc;
        }

        // Volumes aren't namespaced, so only those bound to a claim of this namespace.
        $pvs = [];
        $volumeNames = array_filter(array_map(fn ($pvc) => $pvc->volume_name, $pvcs));
        if (! empty($volumeNames)) {
            $pvList = $this->fetch(
                Auth::SHOW_PERSISTENT_VOLUMES,
                PersistentVolume::class,
                filter: Filter::all(
                    Filter::equal('cluster_uuid', (string) Uuid::fromBytes($this->namespace->cluster_uuid)),
                    Filter::equal('name', array_values($volumeNames))
                )
            );
            foreach ($pvList as $pv) {
                $pvs[$pv->name] = $pv;
            }
        }

        $configMaps = [];
        foreach ($this->fetch(Auth::SHOW_CONFIG_MAPS, ConfigMap::class) as $configMap) {
            $configMaps[$configMap->name] = $configMap;
        }

        $secrets = [];
        foreach ($this->fetch(Auth::SHOW_SECRETS, Secret::class) as $secret) {
            $secrets[$secret->name] = $secret;
        }

        // Config maps and secrets each pod mounts or reads environment variables from, by binary pod uuid and kind.
        $mountsOf = [];
        if (! empty($configMaps) || ! empty($secrets)) {
            // Environment variable references are only stored in the pod's YAML.
            foreach ($this->fetch(Auth::SHOW_PODS, Pod::class, ['yaml']) as $pod) {
                foreach (static::referencedConfig($pod->yaml ?? '') as [$kind, $name]) {
                    $mountsOf[$pod->uuid][$kind][$name] = true;
                }
            }

            $volumes = PodVolume::on(Database::connection())
                ->columns(['pod_uuid', 'volume_name', 'type', 'source'])
                ->filter(Filter::all(
                    Filter::equal('pod.namespace', $this->namespace->name),
                    Filter::equal('type', ['ConfigMap', 'Secret', 'Projected']),
                    // Injected into almost every pod for the service account token and the cluster's CA.
                    Filter::unlike('volume_name', 'kube-api-access-*')
                ));
            foreach ($volumes as $volume) {
                foreach (static::mountedConfig($volume->type, $volume->source) as [$kind, $name]) {
                    $mountsOf[$volume->pod_uuid][$kind][$name] = true;
                }
            }
        }

        // Claims used by each pod, by binary pod uuid.
        $claimsOf = [];
        if (! empty($pvcs)) {
            $podPvcs = PodPvc::on(Database::connection())
                ->columns(['pod_uuid', 'claim_name'])
                ->filter(Filter::equal('pod.namespace', $this->namespace->name));
            foreach ($podPvcs as $podPvc) {
                $claimsOf[$podPvc->pod_uuid][$podPvc->claim_name] = true;
            }
        }

        // Owner of each pod, replica set and job, to find the workload a service's pods belong to.
        $ownerOf = [];
        foreach ([$pods, $replicaSets, $jobs] as $groups) {
            foreach ($groups as $owner => $models) {
                foreach ($models as $model) {
                    $ownerOf[$model->uuid] = $owner;
                }
            }
        }

        // Claims with their volume, by name. A claim used by several pods is drawn under each of them.
        $pvcNodes = [];
        foreach ($pvcs as $name => $pvc) {
            // Unbound claims have no volume name.
            $pv = $pvc->volume_name === null ? null : $pvs[$pvc->volume_name] ?? null;
            $pvcNodes[$name] = $this->node($pvc, $pv === null ? [] : [$this->node($pv)]);
        }

        $configMapNodes = array_map(fn ($configMap) => $this->node($configMap), $configMaps);
        $secretNodes = array_map(fn ($secret) => $this->node($secret), $secrets);

        $usedPvcs = [];
        $usedConfigMaps = [];
        $usedSecrets = [];
        $podNode = function (Model $pod) use (
            $claimsOf,
            $mountsOf,
            $pvcNodes,
            $configMapNodes,
            $secretNodes,
            &$usedPvcs,
            &$usedConfigMaps,
            &$usedSecrets
        ): HtmlElement {
            $claims = array_intersect_key($pvcNodes, $claimsOf[$pod->uuid] ?? []);
            $mountedConfigMaps = array_intersect_key($configMapNodes, $mountsOf[$pod->uuid]['configmap'] ?? []);
            $mountedSecrets = array_intersect_key($secretNodes, $mountsOf[$pod->uuid]['secret'] ?? []);
            $usedPvcs += $claims;
            $usedConfigMaps += $mountedConfigMaps;
            $usedSecrets += $mountedSecrets;

            return $this->node(
                $pod,
                array_merge(array_values($claims), array_values($mountedConfigMaps), array_values($mountedSecrets))
            );
        };

        // By reference, so taken pods aren't drawn again as orphans. An arrow function would capture a copy.
        $podsOf = function (Model $owner) use (&$pods, $podNode): HtmlElement {
            return $this->node(
                $owner,
                array_map($podNode, static::take($pods, $owner->uuid)),
                'pod',
                Filter::equal('pod.owner.owner_uuid', (string) Uuid::fromBytes($owner->uuid))
            );
        };

        // Topmost visible object of each owner chain, keyed by binary uuid.
        $workloads = [];

        foreach ($deployments as $deployment) {
            $workloads[$deployment->uuid] = $this->node(
                $deployment,
                array_map($podsOf, static::take($replicaSets, $deployment->uuid)),
                'replicaset',
                Filter::equal('replica_set.owner.owner_uuid', (string) Uuid::fromBytes($deployment->uuid))
            );
        }

        foreach (array_merge($statefulSets, $daemonSets) as $owner) {
            $workloads[$owner->uuid] = $podsOf($owner);
        }

        foreach ($cronJobs as $cronJob) {
            $workloads[$cronJob->uuid] = $this->node(
                $cronJob,
                array_map($podsOf, static::take($jobs, $cronJob->uuid)),
                'job',
                Filter::equal('job.owner.owner_uuid', (string) Uuid::fromBytes($cronJob->uuid))
            );
        }

        // Whatever is left has no visible owner.
        foreach (array_merge(...array_values($replicaSets), ...array_values($jobs)) as $orphan) {
            $workloads[$orphan->uuid] = $podsOf($orphan);
        }

        foreach (array_merge(...array_values($pods)) as $pod) {
            $workloads[$pod->uuid] = $podNode($pod);
        }

        // A service links to the workloads its pods belong to rather than to the pods, which already sit under
        // their owner. Workloads behind several services are drawn under each of them.
        $serviceNodes = [];
        $behindService = [];
        foreach ($services as $name => $service) {
            $backing = [];
            foreach ($service->pod->columns(['uuid']) as $pod) {
                $uuid = $pod->uuid;
                while (! isset($workloads[$uuid]) && isset($ownerOf[$uuid])) {
                    $uuid = $ownerOf[$uuid];
                }

                if (isset($workloads[$uuid])) {
                    $backing[$uuid] = $workloads[$uuid];
                }
            }

            $serviceNodes[$name] = $this->node($service, array_values($backing));
            $behindService += $backing;
        }

        $roots = [];
        $exposed = [];

        foreach ($ingresses as $ingress) {
            $backends = [];
            foreach ($ingress->backend_service as $backend) {
                if (isset($serviceNodes[$backend->service_name])) {
                    $backends[$backend->service_name] = $serviceNodes[$backend->service_name];
                }
            }

            $tlsSecrets = [];
            foreach ($ingress->ingress_tls as $tls) {
                if ($tls->tls_secret !== null && isset($secretNodes[$tls->tls_secret])) {
                    $tlsSecrets[$tls->tls_secret] = $secretNodes[$tls->tls_secret];
                }
            }

            $roots[] = $this->node(
                $ingress,
                array_merge(array_values($backends), array_values($tlsSecrets)),
                // Services and secrets mixed can't be summarized by a link to a single list.
                empty($tlsSecrets) ? 'service' : null,
                Filter::equal('service.name', array_keys($backends))
            );
            $exposed += $backends;
            $usedSecrets += $tlsSecrets;
        }

        // Used by every pod through the injected kube-api-access volume skipped above, so not unused.
        $usedConfigMaps['kube-root-ca.crt'] = true;

        // Like workloads, a service backing several ingresses is drawn under each of them and only skipped as a root.
        array_push(
            $roots,
            ...array_values(array_diff_key($serviceNodes, $exposed)),
            ...array_values(array_diff_key($workloads, $behindService)),
            // Claims, config maps and secrets nothing uses.
            ...array_values(array_diff_key($pvcNodes, $usedPvcs)),
            ...array_values(array_diff_key($configMapNodes, $usedConfigMaps)),
            ...array_values(array_diff_key($secretNodes, $usedSecrets))
        );

        if ($this->focus !== null) {
            $roots = $this->focusOn($roots);
        }

        if (empty($roots)) {
            return new EmptyState($this->translate('No items found.'));
        }

        $seen = [];
        foreach ($roots as $root) {
            $this->dedupe($root, $seen);
        }

        // Only columns with nodes drawn in them, e.g. no replica set column for a stateful set's context.
        $drawn = [];
        foreach ($roots as $root) {
            $drawn += $this->drawnKinds($root);
        }

        ksort($drawn);
        $this->columns = array_flip(array_keys($drawn));
        foreach ($roots as $root) {
            $this->place($root);
        }

        // Roots are ordered by kind. Group them by it and label every column a group uses, as kinds sharing a column
        // can look alike.
        $groups = [];
        foreach ($roots as $root) {
            $groups[Factory::getKindFromModel($this->models[$root])][] = $root;
        }

        $tree = new HtmlElement('ul');
        $labelled = [];
        foreach ($groups as $group) {
            $kinds = [];
            foreach ($group as $root) {
                $kinds = array_replace_recursive($kinds, $this->drawnKinds($root));
            }

            // Fold the group into the one above if its labels already name all of the group's kinds.
            if (array_replace_recursive($labelled, $kinds) == $labelled) {
                $tree->addHtml(...$group);

                continue;
            }

            ksort($kinds);
            $labelled = $kinds;

            $labels = new HtmlElement('li', Attributes::create(['class' => 'group-label']));
            foreach ($kinds as $column => $kindsInColumn) {
                $labels->addHtml(new HtmlElement(
                    'span',
                    Attributes::create(['data-column' => $this->columns[$column]]),
                    new Text(implode(', ', array_map(
                        fn ($kind) => $this->translate(Factory::createTitle($kind)),
                        array_keys($kindsInColumn)
                    )))
                ));
            }

            $tree->addHtml($labels, ...$group);
        }

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    new Attributes(['class' => 'environment-widget-title']),
                    new Text($this->translate('Environment'))
                ),
                new HtmlElement('div', Attributes::create(['class' => 'namespace-environment']), $tree)
            );
    }

    /**
     * Fetch all objects of the given model in this namespace the user may see
     *
     * @param string $permission
     * @param class-string<Model> $model
     * @param string[] $columns Additional columns
     * @param array<string, string> $orderBy
     * @param ?Filter\Rule $filter Instead of the namespace filter, e.g. for objects that aren't namespaced
     *
     * @return Model[]
     */
    protected function fetch(
        string $permission,
        string $model,
        array $columns = [],
        array $orderBy = [],
        ?Filter\Rule $filter = null
    ): array {
        if (! Auth::getInstance()->hasPermission($permission)) {
            return [];
        }

        $query = $model::on(Database::connection())
            ->columns(array_merge(['uuid', 'name'], $columns))
            ->filter($filter ?? Filter::equal('namespace', $this->namespace->name))
            ->orderBy(array_merge($orderBy, ['name' => 'asc']));

        // Relation columns don't join their table on their own.
        foreach ($columns as $column) {
            if (str_contains($column, '.')) {
                $query->with(strstr($column, '.', true));
            }
        }

        return iterator_to_array(Auth::getInstance()->withRestrictions($permission, $query), false);
    }

    /**
     * Render a node with its children, collapsing all but the first {@see LIMIT} into a link to the filtered list
     *
     * @param Model $model
     * @param HtmlElement[] $children
     * @param ?string $childKind
     * @param ?Filter\Rule $childFilter
     *
     * @return HtmlElement
     */
    protected function node(
        Model $model,
        array $children = [],
        ?string $childKind = null,
        ?Filter\Rule $childFilter = null
    ): HtmlElement {
        $kind = Factory::getKindFromModel($model);

        $li = new HtmlElement(
            'li',
            // data-node identifies the node for links from other parents, see dedupe(). data-column is the kind's
            // column of COLUMNS until place().
            Attributes::create(['data-column' => static::COLUMNS[$kind], 'data-node' => bin2hex($model->uuid)]),
            new Link(
                Environment::renderNode($model),
                Factory::createDetailUrl($kind)->addParams(['id' => (string) Uuid::fromBytes($model->uuid)]),
                Attributes::create(['class' => 'node', 'data-base-target' => '_next', 'title' => $model->name])
            )
        );

        $this->models[$li] = $model;

        if (empty($children)) {
            return $li;
        }

        // Only children of a single kind can be summarized by a link to their list. The context of an object is
        // small enough to show all.
        $shown = $childKind === null || $this->focus !== null ? $children : array_slice($children, 0, static::LIMIT);

        if (count($shown) < count($children)) {
            // Like the summary of the Environment widget: the state of all children if they have one, else their count.
            $states = new ItemCountIndicator();
            foreach ($children as $child) {
                $childModel = $this->models[$child];
                if (isset($childModel->icinga_state)) {
                    $states->addIndicator($childModel->icinga_state, 1);
                }
            }

            // In the column of the children it summarizes.
            $shown[] = new HtmlElement(
                'li',
                Attributes::create(['data-column' => static::COLUMNS[$childKind]]),
                new Link(
                    new HorizontalKeyValue(
                        new KIcon($childKind),
                        count($states) > 0 ? $states : new Text(count($children))
                    ),
                    Url::fromPath('kubernetes/' . Factory::pluralizeKind($childKind))
                        ->setFilter(Filter::all(Filter::equal('namespace', $this->namespace->name), $childFilter)),
                    Attributes::create([
                        'class'            => 'summary',
                        'data-base-target' => '_next',
                        'title'            => sprintf($this->translate('Show all %d'), count($children))
                    ])
                )
            );
        }

        return $li->addHtml(new HtmlElement('ul', null, ...$shown));
    }

    /**
     * Place the given node and its subtree in the columns drawn
     *
     * Only after {@see dedupe()}, as nodes are then in a single list, and with {@see $columns} set.
     *
     * @param HtmlElement $li
     *
     * @return void
     */
    protected function place(HtmlElement $li): void
    {
        $column = $this->columns[(int) $li->getAttributes()->get('data-column')->getValue()];
        $li->setAttribute('data-column', $column);

        $list = static::childList($li);
        if ($list === null) {
            return;
        }

        // Children further right than the next column get a longer edge. The columns all children skip lengthen the
        // stem, so the edge splits as late as possible, only the rest lengthens the edge to the child.
        $skips = [];
        foreach ($list->getContent() as $i => $child) {
            $this->place($child);
            $skips[$i] = (int) $child->getAttributes()->get('data-column')->getValue() - $column - 1;
        }

        $stemSkip = min($skips);
        foreach ($list->getContent() as $i => $child) {
            if ($skips[$i] > $stemSkip) {
                $child->setAttribute('data-skip', $skips[$i] - $stemSkip);
            }
        }

        if ($stemSkip > 0) {
            $list->setAttribute('data-skip', $stemSkip);
        }
    }

    /**
     * Keep only the focused object, its ancestors and its descendants
     *
     * Siblings, e.g. other pods using the same secret, are dropped. Before {@see dedupe()}, as nodes with several
     * parents are then in each of their lists, which tells their parents.
     *
     * @param HtmlElement[] $roots
     *
     * @return HtmlElement[] The new roots, the topmost ancestors
     */
    protected function focusOn(array $roots): array
    {
        $children = fn (HtmlElement $li) => array_filter(
            static::childList($li)?->getContent() ?? [],
            fn ($child) => isset($this->models[$child])
        );

        // All nodes in tree order, and the parents of each, by object id.
        $nodes = [];
        $parents = [];
        $walk = function (HtmlElement $li) use (&$walk, &$nodes, &$parents, $children): void {
            if (isset($nodes[spl_object_id($li)])) {
                return;
            }

            $nodes[spl_object_id($li)] = $li;
            foreach ($children($li) as $child) {
                $parents[spl_object_id($child)][spl_object_id($li)] = $li;
                $walk($child);
            }
        };
        foreach ($roots as $root) {
            $walk($root);
        }

        $focus = null;
        foreach ($nodes as $li) {
            if ($this->models[$li]->uuid === $this->focus->uuid) {
                $focus = $li;

                break;
            }
        }

        if ($focus === null) {
            return [];
        }

        $focus->getAttributes()->add('class', 'focus');

        $keep = [spl_object_id($focus) => true];
        $down = function (HtmlElement $li) use (&$down, &$keep, $children): void {
            foreach ($children($li) as $child) {
                $keep[spl_object_id($child)] = true;
                $down($child);
            }
        };
        $up = function (HtmlElement $li) use (&$up, &$keep, $parents): void {
            foreach ($parents[spl_object_id($li)] ?? [] as $parent) {
                $keep[spl_object_id($parent)] = true;
                $up($parent);
            }
        };
        $down($focus);
        $up($focus);

        // Drop everything else from the lists of the nodes kept.
        foreach (array_intersect_key($nodes, $keep) as $li) {
            $list = static::childList($li);
            foreach ($children($li) as $child) {
                if (! isset($keep[spl_object_id($child)])) {
                    $list->remove($child);
                }
            }

            if ($list !== null && empty($list->getContent())) {
                $li->remove($list);
            }
        }

        return array_values(array_filter(
            array_intersect_key($nodes, $keep),
            fn ($id) => empty(array_intersect_key($parents[$id] ?? [], $keep)),
            ARRAY_FILTER_USE_KEY
        ));
    }

    /**
     * Draw every node once, where it first appears in the tree, and link its other parents to it instead
     *
     * Nodes with several parents, e.g. a config map used by many pods, are the same element in each parent's list.
     * Repeats are removed from their parent's list, which gets the node's id in data-links. Those links are drawn by
     * namespace-environment.js, as they can't follow the tree.
     *
     * @param HtmlElement $li
     * @param array<int, true> $seen Ids of the nodes drawn so far
     *
     * @return void
     */
    protected function dedupe(HtmlElement $li, array &$seen): void
    {
        $seen[spl_object_id($li)] = true;

        $list = static::childList($li);
        if ($list === null) {
            return;
        }

        $links = [];
        foreach ($list->getContent() as $child) {
            if (! isset($this->models[$child])) {
                // The summary.
                continue;
            }

            if (isset($seen[spl_object_id($child)])) {
                $list->remove($child);
                $links[] = $child->getAttributes()->get('data-node')->getValue();
            } else {
                $this->dedupe($child, $seen);
            }
        }

        if (! empty($links)) {
            $li->setAttribute('data-links', implode(' ', $links));
        }

        if (empty($list->getContent())) {
            $li->remove($list);
        }
    }

    /**
     * Get the kinds drawn in the given node's subtree by their column of {@see COLUMNS}, for columns and labels
     *
     * Only after {@see dedupe()}, as nodes drawn elsewhere don't count.
     *
     * @param HtmlElement $li
     *
     * @return array<int, array<string, true>>
     */
    protected function drawnKinds(HtmlElement $li): array
    {
        $kind = Factory::getKindFromModel($this->models[$li]);
        $kinds = [static::COLUMNS[$kind] => [$kind => true]];

        foreach (static::childList($li)?->getContent() ?? [] as $child) {
            if (isset($this->models[$child])) {
                $kinds = array_replace_recursive($kinds, $this->drawnKinds($child));
            }
        }

        return $kinds;
    }

    /**
     * Get the list of the given node's children, if any
     *
     * @param HtmlElement $li
     *
     * @return ?HtmlElement
     */
    protected static function childList(HtmlElement $li): ?HtmlElement
    {
        foreach ($li->getContent() as $content) {
            if ($content instanceof HtmlElement && $content->getTag() === 'ul') {
                return $content;
            }
        }

        return null;
    }

    /**
     * Get the config maps and secrets a pod volume mounts
     *
     * The type is the field name of the volume source in the Kubernetes API, the source is that field as JSON.
     *
     * @param string $type
     * @param string $source
     *
     * @return array<array{string, string}> Kind and name of each
     */
    public static function mountedConfig(string $type, string $source): array
    {
        $source = json_decode($source, true);
        if (! is_array($source)) {
            return [];
        }

        $mounts = match ($type) {
            'ConfigMap' => [['configmap', $source['name'] ?? null]],
            'Secret'    => [['secret', $source['secretName'] ?? null]],
            'Projected' => array_map(
                fn ($projection) => match (true) {
                    isset($projection['configMap']) => ['configmap', $projection['configMap']['name'] ?? null],
                    isset($projection['secret'])    => ['secret', $projection['secret']['name'] ?? null],
                    default                         => [null, null]
                },
                $source['sources'] ?? []
            ),
            default     => []
        };

        return array_values(array_filter($mounts, fn ($mount) => is_string($mount[0]) && is_string($mount[1])));
    }

    /**
     * Get the config maps and secrets a pod reads environment variables from, according to its YAML
     *
     * Covers envFrom (configMapRef, secretRef) and valueFrom (configMapKeyRef, secretKeyRef) of all containers.
     *
     * ponytail: pattern over the YAML the daemon writes, not a YAML parser. A table of env references written by the
     * daemon, like pod_pvc, would be exact and spare loading every pod's YAML.
     *
     * @param string $yaml
     *
     * @return array<array{string, string}> Kind and name of each
     */
    public static function referencedConfig(string $yaml): array
    {
        // The reference key, then its more indented lines, which hold the name.
        preg_match_all(
            '/^( *)(?:- )?(configMapRef|configMapKeyRef|secretRef|secretKeyRef):[ \t]*\n((?:\1 +\S.*(?:\n|$))*)/m',
            $yaml,
            $matches,
            PREG_SET_ORDER
        );

        $references = [];
        foreach ($matches as [, , $key, $block]) {
            if (preg_match('/^ +name: *[\'"]?([^\'"\s]+)/m', $block, $name)) {
                $references[] = [str_starts_with($key, 'configMap') ? 'configmap' : 'secret', $name[1]];
            }
        }

        return $references;
    }

    /**
     * Group the given models by the binary uuid of their owner, or `''` if they have none
     *
     * @param Model[] $models
     *
     * @return array<string, Model[]>
     */
    protected static function groupByOwner(array $models): array
    {
        $groups = [];
        foreach ($models as $model) {
            $groups[$model->owner->owner_uuid ?? ''][] = $model;
        }

        return $groups;
    }

    /**
     * Remove and return the models owned by the given owner from the groups
     *
     * @param array<string, Model[]> $groups
     * @param string $ownerUuid
     *
     * @return Model[]
     */
    protected static function take(array &$groups, string $ownerUuid): array
    {
        $models = $groups[$ownerUuid] ?? [];
        unset($groups[$ownerUuid]);

        return $models;
    }
}

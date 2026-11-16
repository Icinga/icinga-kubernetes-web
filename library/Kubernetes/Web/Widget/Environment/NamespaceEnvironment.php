<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\Environment;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Model\StatefulSet;
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
use ipl\Web\Widget\Link;
use Ramsey\Uuid\Uuid;

/**
 * Tree of all workloads in a namespace, from ingresses and services down to pods
 *
 * Edges follow owner references (Deployment → ReplicaSet → Pod, CronJob → Job → Pod, StatefulSet/DaemonSet → Pod)
 * and ingress backends (Ingress → Service). Service → Pod edges are omitted, as pods already appear under their
 * owner. Objects whose owner isn't shown become roots.
 */
class NamespaceEnvironment implements ValidHtml
{
    use Translation;

    /** @var int Children shown per parent, the rest collapse into a summary link */
    protected const LIMIT = 3;

    /** @var array<string, int> Column of each kind, so objects of the same kind line up below each other */
    protected const COLUMNS = [
        'ingress'     => 1,
        'service'     => 2,
        'deployment'  => 3,
        'cronjob'     => 3,
        'replicaset'  => 4,
        'job'         => 4,
        'statefulset' => 4,
        'daemonset'   => 4,
        'pod'         => 5
    ];

    /** @var array<int, int> Non-empty columns of {@see COLUMNS}, mapped to their position starting at 0 */
    protected array $columns = [];

    public function __construct(protected NamespaceModel $namespace)
    {
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

        // Every fetched object is drawn, so a column is empty exactly if none of its kinds were fetched. Parent and
        // child kinds sit in neighbouring columns that are both non-empty, so dropping empty columns keeps edges short.
        $columns = array_unique(array_intersect_key(static::COLUMNS, array_filter([
            'ingress'     => $ingresses,
            'service'     => $services,
            'deployment'  => $deployments,
            'cronjob'     => $cronJobs,
            'replicaset'  => $replicaSets,
            'job'         => $jobs,
            'statefulset' => $statefulSets,
            'daemonset'   => $daemonSets,
            'pod'         => $pods
        ])));
        sort($columns);
        $this->columns = array_flip($columns);

        // By reference, so taken pods aren't drawn again as orphans. An arrow function would capture a copy.
        $podsOf = function (Model $owner) use (&$pods): HtmlElement {
            return $this->node(
                $owner,
                array_map(fn ($pod) => $this->node($pod), static::take($pods, $owner->uuid)),
                'pod',
                Filter::equal('pod.owner.owner_uuid', (string) Uuid::fromBytes($owner->uuid))
            );
        };

        $roots = [];
        $exposed = [];

        foreach ($ingresses as $ingress) {
            $backends = [];
            foreach ($ingress->backend_service as $backend) {
                if (isset($services[$backend->service_name])) {
                    $backends[$backend->service_name] = $services[$backend->service_name];
                }
            }

            $roots[] = $this->node(
                $ingress,
                array_map(fn ($service) => $this->node($service), array_values($backends)),
                'service',
                Filter::equal('service.name', array_keys($backends))
            );
            $exposed += $backends;
        }

        // A service can back several ingresses, so it is drawn under each of them and only skipped as a root.
        foreach (array_diff_key($services, $exposed) as $service) {
            $roots[] = $this->node($service);
        }

        foreach ($deployments as $deployment) {
            $roots[] = $this->node(
                $deployment,
                array_map($podsOf, static::take($replicaSets, $deployment->uuid)),
                'replicaset',
                Filter::equal('replica_set.owner.owner_uuid', (string) Uuid::fromBytes($deployment->uuid))
            );
        }

        foreach ($statefulSets as $statefulSet) {
            $roots[] = $podsOf($statefulSet);
        }

        foreach ($daemonSets as $daemonSet) {
            $roots[] = $podsOf($daemonSet);
        }

        foreach ($cronJobs as $cronJob) {
            $roots[] = $this->node(
                $cronJob,
                array_map($podsOf, static::take($jobs, $cronJob->uuid)),
                'job',
                Filter::equal('job.owner.owner_uuid', (string) Uuid::fromBytes($cronJob->uuid))
            );
        }

        // Whatever is left has no visible owner.
        foreach (array_merge(...array_values($replicaSets), ...array_values($jobs)) as $orphan) {
            $roots[] = $podsOf($orphan);
        }

        foreach (array_merge(...array_values($pods)) as $pod) {
            $roots[] = $this->node($pod);
        }

        if (empty($roots)) {
            return new HtmlDocument();
        }

        $tree = new HtmlElement('ul', null, ...$roots);

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
     *
     * @return Model[]
     */
    protected function fetch(string $permission, string $model, array $columns = [], array $orderBy = []): array
    {
        if (! Auth::getInstance()->hasPermission($permission)) {
            return [];
        }

        $query = $model::on(Database::connection())
            ->columns(array_merge(['uuid', 'name'], $columns))
            ->filter(Filter::equal('namespace', $this->namespace->name))
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
            Attributes::create(['class' => 'column-' . $this->columns[static::COLUMNS[$kind]]]),
            new Link(
                Environment::renderNode($model),
                Factory::createDetailUrl($kind)->addParams(['id' => (string) Uuid::fromBytes($model->uuid)]),
                Attributes::create(['class' => 'node', 'data-base-target' => '_next', 'title' => $model->name])
            )
        );

        if (empty($children)) {
            return $li;
        }

        $list = new HtmlElement('ul', null, ...array_slice($children, 0, static::LIMIT));

        if (count($children) > static::LIMIT) {
            $list->addHtml(new HtmlElement(
                'li',
                null,
                new Link(
                    [new KIcon($childKind), new Text(sprintf('+%d', count($children) - static::LIMIT))],
                    Url::fromPath('kubernetes/' . Factory::pluralizeKind($childKind))
                        ->setFilter(Filter::all(Filter::equal('namespace', $this->namespace->name), $childFilter)),
                    Attributes::create(['class' => 'summary', 'data-base-target' => '_next'])
                )
            ));
        }

        return $li->addHtml($list);
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

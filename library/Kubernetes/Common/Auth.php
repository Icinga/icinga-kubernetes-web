<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use BadMethodCallException;
use Icinga\Authentication\Auth as IcingaAuth;
use Icinga\Exception\ConfigurationError;
use Icinga\User;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;

class Auth
{
    public const SHOW_CONFIG_MAPS = 'kubernetes/config-maps/show';
    public const SHOW_CRON_JOBS = 'kubernetes/cron-jobs/show';
    public const SHOW_DAEMON_SETS = 'kubernetes/daemon-sets/show';
    public const SHOW_DEPLOYMENTS = 'kubernetes/deployments/show';
    public const SHOW_EVENTS = 'kubernetes/events/show';
    public const SHOW_INGRESSES = 'kubernetes/ingresses/show';
    public const SHOW_JOBS = 'kubernetes/jobs/show';
    public const SHOW_NAMESPACES = 'kubernetes/namespaces/show';
    public const SHOW_NODES = 'kubernetes/nodes/show';
    public const SHOW_PERSISTENT_VOLUME_CLAIMS = 'kubernetes/persistent-volume-claims/show';
    public const SHOW_PERSISTENT_VOLUMES = 'kubernetes/persistent-volumes/show';
    public const SHOW_PODS = 'kubernetes/pods/show';
    public const SHOW_REPLICA_SETS = 'kubernetes/replica-sets/show';
    public const SHOW_SECRETS = 'kubernetes/secrets/show';
    public const SHOW_SERVICES = 'kubernetes/services/show';
    public const SHOW_STATEFUL_SETS = 'kubernetes/stateful-sets/show';

    public const PERMISSIONS = [
        'ConfigMap'             => self::SHOW_CONFIG_MAPS,
        'CronJob'               => self::SHOW_CRON_JOBS,
        'DaemonSet'             => self::SHOW_DAEMON_SETS,
        'Deployment'            => self::SHOW_DEPLOYMENTS,
        'Event'                 => self::SHOW_EVENTS,
        'Ingress'               => self::SHOW_INGRESSES,
        'Job'                   => self::SHOW_JOBS,
        'Namespace'             => self::SHOW_NAMESPACES,
        'Node'                  => self::SHOW_NODES,
        'PersistentVolume'      => self::SHOW_PERSISTENT_VOLUMES,
        'PersistentVolumeClaim' => self::SHOW_PERSISTENT_VOLUME_CLAIMS,
        'Pod'                   => self::SHOW_PODS,
        'ReplicaSet'            => self::SHOW_REPLICA_SETS,
        'Secret'                => self::SHOW_SECRETS,
        'Service'               => self::SHOW_SERVICES,
        'StatefulSet'           => self::SHOW_STATEFUL_SETS,
    ];

    protected IcingaAuth $auth;

    protected User $user;

    private static self $instance;

    private function __construct()
    {
        $this->auth = IcingaAuth::getInstance();
        $this->user = $this->auth->getUser();
    }

    public static function getInstance(): static
    {
        if (! isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Proxy Auth method calls
     *
     * @param string $name The name of the Auth method to call
     * @param array $arguments Arguments for the method to call
     *
     * @return mixed
     *
     * @throws BadMethodCallException If the called method does not exist
     *
     */
    public function __call(string $name, array $arguments)
    {
        if (! method_exists($this->auth, $name)) {
            $class = get_class($this);
            $message = "Call to undefined method $class::$name";

            throw new BadMethodCallException($message);
        }

        return call_user_func_array([$this->auth, $name], $arguments);
    }

    /**
     * Checks if the user has the required permissions to show Kubernetes YAML.
     *
     * @return bool Returns `true` if the user has necessary permission, otherwise returns `false`.
     */
    public function canShowYaml(): bool
    {
        return $this->auth->hasPermission('kubernetes/yaml/show');
    }

    /**
     * Checks if a user has permission to list resources of the specified kind.
     *
     *  Example:
     *  If the `kind` is "Pod" and the user has the required permission for listing Pods,
     *  this method returns true. If no specific permissions are defined for the `kind`,
     *  the method defaults to allowing access.
     *
     * @param string $kind
     *
     * @return bool
     */
    public function canList(string $kind): bool
    {
        if (isset(self::PERMISSIONS[$kind])) {
            $permission = self::PERMISSIONS[$kind];

            return $this->auth->hasPermission($permission);
        }

        return true;
    }

    /**
     * Apply module restrictions depending on what is queried
     *
     * This will apply `kubernetes/filter/resources` restrictions based on the type of the resource being queried:
     * - For `namespace`, it adjusts the restriction to use 'name' instead of 'namespace'.
     * - For `node` or `persistent_volume`, any restrictions involving `namespace` are ignored.
     * It also applies role-based restrictions for the user and checks their permissions to list the resources.
     *
     * @param string $permission The permission required to access the resource.
     * @param Query $query The query to which the restrictions will be applied.
     *
     * @return Query The cloned query with applied restrictions.
     */
    public function withRestrictions(string $permission, Query $query): Query
    {
        $q = clone $query; // The original query may be part of a model and those shouldn't change implicitly

        $table = $q->getModel()->getTableName();
        $queryFilter = Filter::any();

        foreach ($this->user->getRoles() as $role) {
            if (
                $role->grants($permission)
                && $restriction = $role->getRestrictions('kubernetes/filter/resources')
            ) {
                $queryFilter->add(
                    $this->parseRestriction(
                        $table,
                        $restriction,
                        'kubernetes/filter/resources'
                    )
                );
            }
        }

        $q->filter($queryFilter);

        return $q;
    }

    /**
     * Parse the given restriction
     *
     * @param string $table
     * @param string $queryString
     * @param string $restriction The name of the restriction
     *
     * @return Filter\Rule
     */
    protected function parseRestriction(string $table, string $queryString, string $restriction): Filter\Rule
    {
        $allowedColumns = [
            'name',
            'namespace'
        ];

        return QueryString::fromString("internal=internal&$queryString")
            ->on(
                QueryString::ON_CONDITION,
                function (Filter\Condition $condition) use (
                    $restriction,
                    $queryString,
                    $allowedColumns,
                    $table
                ) {
                    $column = $condition->getColumn();

                    if ($column === 'internal') {
                        $condition->metaData()->set('remove', true);

                        return;
                    }

                    if (! in_array($column, $allowedColumns)) {
                        throw new ConfigurationError(
                            t(
                                'Cannot apply restriction %s using the filter %s.'
                                . ' You can only use the following columns: %s'
                            ),
                            $restriction,
                            $queryString,
                            implode(', ', $allowedColumns)
                        );
                    }

                    if (($table === 'node' || $table === 'persistent_volume') && $column === 'namespace') {
                        $condition->metaData()->set('remove', true);

                        return;
                    }

                    if ($table === 'namespace' && $column === 'namespace') {
                        // Table namespace does not have column namespace.
                        $column = 'name';
                    }


                    $condition->setColumn("$table.$column");
                }
            )
            ->on(
                QueryString::ON_CHAIN,
                function (Filter\Chain $chain) {
                    foreach ($chain as $filter) {
                        if (
                            $filter instanceof Filter\Condition
                            && $filter->metaData()->get('remove', false) === true
                        ) {
                            $chain->remove($filter);
                        }
                    }
                }
            )
            ->parse();
    }
}

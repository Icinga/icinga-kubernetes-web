<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use BadMethodCallException;
use Icinga\Authentication\Auth as IcingaAuth;
use Icinga\Exception\ConfigurationError;
use Icinga\User;
use ipl\Orm\Query;
use ipl\Orm\UnionQuery;
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
    public const SHOW_REPLICA_SET = 'kubernetes/replica-sets/show';
    public const SHOW_SECRETS = 'kubernetes/secrets/show';
    public const SHOW_SERVICES = 'kubernetes/services/show';
    public const SHOW_STATEFUL_SET = 'kubernetes/stateful-sets/show';

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
    public function __call($name, array $arguments)
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
     * Apply module restrictions depending on what is queried
     *
     * @param Query $query
     *
     * @return void
     */
    public function applyRestrictions(Query $query, string $permission): void
    {
        if ($this->user->isUnrestricted()) {
            return;
        }

        if ($query instanceof UnionQuery) {
            $queries = $query->getUnions();
        } else {
            $queries = [$query];
        }

        foreach ($queries as $query) {
            $table = $query->getModel()->getTableName();

            $queryFilter = Filter::any();
            foreach ($this->user->getRoles() as $role) {
                $roleFilter = Filter::all();

                if ($role->grants($permission) && $restriction = $role->getRestrictions(
                        'kubernetes/filter/resources'
                    )) {
                    if ($table === 'namespace' && str_contains($restriction, 'namespace')) {
                        $restriction = str_replace('namespace', 'name', $restriction);
                    } elseif ($table === 'node' && str_contains($restriction, 'namespace')) {
                        continue;
                    }

                    $restriction = preg_replace(
                        '/(\w+\.\w+|\w+)([!~<>=]=|[~!=<>])/',
                        $table . '.$1$2',
                        $restriction
                    );

                    $roleFilter->add($this->parseRestriction($table, $restriction, 'kubernetes/filter/resources'));
                }

                if (! $roleFilter->isEmpty()) {
                    $queryFilter->add($roleFilter);
                }
            }

            $query->filter($queryFilter);
        }
    }

    /**
     * Parse the given restriction
     *
     * @param string $queryString
     * @param string $restriction The name of the restriction
     *
     * @return Filter\Rule
     */
    protected function parseRestriction(string $table, string $queryString, string $restriction): Filter\Rule
    {
        $allowedColumns = [
            'name'      => function ($c) use ($table) {
                return preg_match('/^' . preg_quote($table) . '\.(name|namespace)$/', $c);
            },
            'namespace' => function ($c) use ($table) {
                return preg_match('/^' . preg_quote($table) . '\.(name|namespace)$/', $c);
            }
        ];

        return QueryString::fromString($queryString)
            ->on(
                QueryString::ON_CONDITION,
                function (Filter\Condition $condition) use (
                    $restriction,
                    $queryString,
                    $allowedColumns
                ) {
                    foreach ($allowedColumns as $column) {
                        if (is_callable($column)) {
                            if ($column($condition->getColumn())) {
                                return;
                            } elseif ($column === $condition->getColumn()) {
                                return;
                            }
                        }
                    }

                    throw new ConfigurationError(
                        t(
                            'Cannot apply restriction %s using the filter %s.'
                            . ' You can only use the following columns: %s'
                        ),
                        $restriction,
                        $queryString,
                        join(
                            ', ',
                            array_map(
                                function ($k, $v) {
                                    return is_string($k) ? $k : $v;
                                },
                                array_keys($allowedColumns),
                                $allowedColumns
                            )
                        )
                    );
                }
            )->parse();
    }
}

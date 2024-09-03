<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Authentication\Auth as IcingaAuth;
use Icinga\Exception\ConfigurationError;
use Icinga\User;
use ipl\Orm\Query;
use ipl\Orm\UnionQuery;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;

class Auth
{
    protected IcingaAuth $auth;

    protected User $user;

    private static self $instance;

    private function __construct()
    {
        $this->auth = IcingaAuth::getInstance();
    }

    public static function getInstance(): static
    {
        if (! isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Apply module restrictions depending on what is queried
     *
     * @param Query $query
     *
     * @return void
     */
    public function applyRestrictions(Query $query): void
    {
        $this->user = $this->auth->getUser();
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

                if (($restriction = $role->getRestrictions('kubernetes/filter/resources'))) {
                    $restriction = preg_replace(
                        '/(\w+\.\w+|\w+)([!~<>=]=|[~!=<>])/',
                        $table . '.$1$2',
                        $restriction
                    );

                    $roleFilter->add($this->parseRestriction($restriction, 'kubernetes/filter/resources'));
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
    protected function parseRestriction(string $queryString, string $restriction): Filter\Rule
    {
        $allowedColumns = [
            '(config_map|cron_job|daemon_set|deployment|event|ingress|job|namespace|persistent_volume|pod|pvc|replica_set|secret|service|stateful_set).' .
            '(name|namespace|label.name|label.value|annotation.name|annotation.value)' => function ($c) {
                return preg_match(
                    '/^(config_map|cron_job|daemon_set|deployment|event|ingress|job|namespace|persistent_volume|pod|pvc|replica_set|secret|service|stateful_set)\.' .
                    '(name|namespace|label.name|label.value|annotation.name|annotation.value)$/',
                    $c
                );
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

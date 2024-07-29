<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\ProvidedHook\Notifications;

use Generator;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Web\ContainerList;
use Icinga\Module\Kubernetes\Web\DaemonSetList;
use Icinga\Module\Kubernetes\Web\DeploymentList;
use Icinga\Module\Kubernetes\Web\NodeList;
use Icinga\Module\Kubernetes\Web\PodList;
use Icinga\Module\Kubernetes\Web\ReplicaSetList;
use Icinga\Module\Kubernetes\Web\StatefulSetList;
use Icinga\Module\Notifications\Hook\ObjectsRendererHook;
use ipl\Html\Attributes;
use ipl\Html\FormattedString;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\StateBall;
use Ramsey\Uuid\Uuid;

class ObjectsRenderer extends ObjectsRendererHook
{
    public function getObjectNames(array $objectIdTags): Generator
    {
        yield from $this->yieldObjectsResults($objectIdTags, false);
    }

    public function getHtmlForObjectNames(array $objectIdTags): Generator
    {
        yield from $this->yieldObjectsResults($objectIdTags, true);
    }

    public function getSourceType(): string
    {
        return 'kubernetes';
    }

    public function createObjectLink(array $objectIdTag): ?ValidHtml
    {
        if (! isset($objectIdTag['resource']) || ! isset($objectIdTag['uuid'])) {
            return null;
        }

        $filter = Filter::equal('uuid', Uuid::fromString($objectIdTag['uuid'])->getBytes());
        switch ($objectIdTag['resource']) {
            case 'container':
                $container = Container::on(Database::connection())->filter($filter);
                return new ContainerList($container);
            case 'pod':
                $pod = Pod::on(Database::connection())->filter($filter);
                return new PodList($pod);
            case 'deployment':
                $deployment = Deployment::on(Database::connection())->filter($filter);
                return new DeploymentList($deployment);
            case 'node':
                $node = Node::on(Database::connection())->filter($filter);
                return new NodeList($node);
            case 'daemon_set':
                $daemonSet = DaemonSet::on(Database::connection())->filter($filter);
                return new DaemonSetList($daemonSet);
            case 'replica_set':
                $replicaSet = ReplicaSet::on(Database::connection())->filter($filter);
                return new ReplicaSetList($replicaSet);
            case 'stateful_set':
                $statefulSet = StatefulSet::on(Database::connection())->filter($filter);
                return new StatefulSetList($statefulSet);
            default:
                return null;
        }
    }

    /**
     * Yield objects names formatted in {@see FormattedString HTML} or plain string based on the `$asHtml` param.
     *
     * @param array<array<string, string>> $objectIdTags A list of object ID tags of Icinga for Kubernetes objects
     * @param bool $asHtml Whether to yield the formatted objects names in HTML string
     *
     * @return Generator<array<string, string>, string> Yields the formatted objects names wither their ID tags as keys
     */
    protected function yieldObjectsResults(array $objectIdTags, bool $asHtml): Generator
    {
        foreach ($objectIdTags as $idTags) {
            if (! isset($idTags['resource']) || ! isset($idTags['uuid'])) {
                continue;
            }

            $filter = Filter::equal('uuid', Uuid::fromString($idTags['uuid'])->getBytes());
            switch ($idTags['resource']) {
                case 'container':
                    $query = Container::on(Database::connection())
                        ->with('pod')
                        ->filter($filter);
                    foreach ($query as $obj) {
                        if (! $asHtml) {
                            yield $idTags => sprintf(t('%s - %s', '<container> - <pod>'), $obj->name, $obj->pod->name);
                        } else {
                            $podDetail = [
                                new StateBall($obj->pod->icinga_state, StateBall::SIZE_MEDIUM),
                                Text::create(' '),
                                Text::create($obj->pod->name),
                            ];

                            yield $idTags => Html::sprintf(
                                t('%s - %s', '<container> - <pod>'),
                                new HtmlElement(
                                    'span',
                                    Attributes::create(['class' => 'subject']),
                                    Text::create($obj->name)
                                ),
                                new HtmlElement('span', Attributes::create(['class' => 'subject']), ...$podDetail)
                            );
                        }

                        break;
                    }
                    break;
                case 'pod':
                    $query = Pod::on(Database::connection())
                        ->with('node')
                        ->filter($filter);

                    foreach ($query as $obj) {
                        if (! $asHtml) {
                            yield $idTags => sprintf(t('%s - %s', '<pod> - <node>'), $obj->name, $obj->node->name);
                        } else {
                            $nodeDetail = [
                                new StateBall($obj->node->icinga_state, StateBall::SIZE_MEDIUM),
                                Text::create(' '),
                                Text::create($obj->node->name),
                            ];

                            yield $idTags => Html::sprintf(
                                t('%s - %s', '<pod> - <node>'),
                                new HtmlElement(
                                    'span',
                                    Attributes::create(['class' => 'subject']),
                                    Text::create($obj->name)
                                ),
                                new HtmlElement('span', Attributes::create(['class' => 'subject']), ...$nodeDetail)
                            );
                        }

                        break;
                    }
                    break;
                case 'deployment':
                    $query = Deployment::on(Database::connection())->filter($filter);
                    foreach ($query as $obj) {
                        if (! $asHtml) {
                            yield $idTags => sprintf(t('%s', '<deployment>'), $obj->name);
                        } else {
                            yield $idTags => new HtmlElement(
                                'span',
                                Attributes::create(['class' => 'subject']),
                                Text::create($obj->name)
                            );
                        }

                        break;
                    }
                    break;
                case 'node':
                    $query = Node::on(Database::connection())->filter($filter);
                    foreach ($query as $obj) {
                        if (! $asHtml) {
                            yield $idTags => sprintf(t('%s', '<node>'), $obj->name);
                        } else {
                            yield $idTags => new HtmlElement(
                                'span',
                                Attributes::create(['class' => 'subject']),
                                Text::create($obj->name)
                            );
                        }

                        break;
                    }
                    break;
                case 'daemon_set':
                    $query = DaemonSet::on(Database::connection())->filter($filter);
                    foreach ($query as $obj) {
                        if (! $asHtml) {
                            yield $idTags => sprintf(t('%s', '<daemon_set>'), $obj->name);
                        } else {
                            yield $idTags => new HtmlElement(
                                'span',
                                Attributes::create(['class' => 'subject']),
                                Text::create($obj->name)
                            );
                        }

                        break;
                    }
                    break;
                case 'replica_set':
                    $query = ReplicaSet::on(Database::connection())->filter($filter);
                    foreach ($query as $obj) {
                        if (! $asHtml) {
                            yield $idTags => sprintf(t('%s', '<replica_set>'), $obj->name);
                        } else {
                            yield $idTags => new HtmlElement(
                                'span',
                                Attributes::create(['class' => 'subject']),
                                Text::create($obj->name)
                            );
                        }

                        break;
                    }
                    break;
                case 'stateful_set':
                    $query = StatefulSet::on(Database::connection())->filter($filter);
                    foreach ($query as $obj) {
                        if (! $asHtml) {
                            yield $idTags => sprintf(t('%s', '<stateful_set>'), $obj->name);
                        } else {
                            yield $idTags => new HtmlElement(
                                'span',
                                Attributes::create(['class' => 'subject']),
                                Text::create($obj->name)
                            );
                        }

                        break;
                    }
            }
        }
    }
}

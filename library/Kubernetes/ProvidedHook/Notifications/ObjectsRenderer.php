<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\ProvidedHook\Notifications;

use Generator;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\Widget\KIcon;
use Icinga\Module\Notifications\Hook\ObjectsRendererHook;
use ipl\Html\Attributes;
use ipl\Html\FormattedString;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use Ramsey\Uuid\Uuid;

class ObjectsRenderer extends ObjectsRendererHook
{
    use Translation;

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

        $html = new ResourceList(
            Factory::fetchResource($objectIdTag['resource'])
                ->filter(Filter::equal('uuid', Uuid::fromString($objectIdTag['uuid'])->getBytes()))
        );
        // TODO(el): Icinga Notifications Web now forcefully adds the target, which results in having it twice,
        // ultimately leading to JS errors.
        $html->removeAttribute('data-base-target');

        return $html;
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
        $clusterNames = [];

        foreach ($objectIdTags as $idTags) {
            if (! isset($idTags['resource']) || ! isset($idTags['name'])) {
                continue;
            }

            $clusterName = 'default';
            if (isset($idTags['cluster_uuid'])) {
                $clusterNames[$idTags['cluster_uuid']] ??= Cluster::on(Database::connection())
                    ->columns('name')
                    ->filter(Filter::equal('uuid', Uuid::fromString($idTags['cluster_uuid'])->getBytes()))
                    ->first()
                    ?->name ?? $idTags['cluster_uuid'];

                $clusterName = $clusterNames[$idTags['cluster_uuid']] ?? $idTags['cluster_uuid'];
            }

            switch ($idTags['resource']) {
                case 'node':
                    if (! $asHtml) {
                        yield $idTags => sprintf('%s@%s', $idTags['name'], $clusterName);
                    } else {
                        yield $idTags => (new HtmlDocument())
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'subject']),
                                new Icon('share-nodes'),
                                new Text($idTags['name'])
                            ))
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'cluster-name']),
                                new Icon('circle-nodes'),
                                new Text($clusterName)
                            ));
                    }

                    break;
                default:
                    if (! $asHtml) {
                        yield $idTags => sprintf('%s/%s@%s', $idTags['namespace'], $idTags['name'], $clusterName);
                    } else {
                        yield $idTags => (new HtmlDocument())
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'namespace-badge']),
                                new KIcon('namespace'),
                                new Text($idTags['namespace'])
                            ))
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'subject']),
                                Factory::createIcon($idTags['resource']),
                                new Text($idTags['name'])
                            ))
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'cluster-name']),
                                new Icon('circle-nodes'),
                                new Text($clusterName)
                            ));
                    }

                    break;
            }
        }
    }
}

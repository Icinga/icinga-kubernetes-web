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

    private const HUMAN_OBJECT_TAG_ORDER = [
        'name',
        'resource',
        'namespace'
    ];

    private const FULL_OBJECT_TAG_ORDER = [
        ...self::HUMAN_OBJECT_TAG_ORDER,
        'uuid',
        'cluster_uuid'
    ];

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
                if ($idTags !== []) {
                    yield $idTags => $this->formatObjectIdTags($idTags);
                }

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
                        yield $idTags => $this->formatObjectIdTags($idTags, $clusterName);
                    } else {
                        yield $idTags => (new HtmlDocument())
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'subject', 'title' => $this->formatObjectIdTags($idTags, $clusterName, true)]),
                                new Icon('share-nodes'),
                                new Text('name=' . $idTags['name'])
                            ))
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'object-tag']),
                                new Text('resource=' . $idTags['resource'])
                            ))
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'cluster-name']),
                                new Icon('circle-nodes'),
                                new Text('cluster=' . $clusterName)
                            ));
                    }

                    break;
                default:
                    if (! $asHtml) {
                        yield $idTags => $this->formatObjectIdTags($idTags, $clusterName);
                    } else {
                        yield $idTags => (new HtmlDocument())
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'subject', 'title' => $this->formatObjectIdTags($idTags, $clusterName, true)]),
                                Factory::createIcon($idTags['resource']),
                                new Text('name=' . $idTags['name'])
                            ))
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'object-tag']),
                                new Text('resource=' . $idTags['resource'])
                            ))
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'namespace-badge']),
                                new KIcon('namespace'),
                                new Text('namespace=' . ($idTags['namespace'] ?? ''))
                            ))
                            ->addHtml(new HtmlElement(
                                'span',
                                new Attributes(['class' => 'cluster-name']),
                                new Icon('circle-nodes'),
                                new Text('cluster=' . $clusterName)
                            ));
                    }

                    break;
            }
        }
    }

    protected function formatObjectIdTags(array $idTags, ?string $clusterName = null, bool $includeIdentifiers = false): string
    {
        $tags = [];

        foreach ($includeIdentifiers ? self::FULL_OBJECT_TAG_ORDER : self::HUMAN_OBJECT_TAG_ORDER as $tag) {
            if (isset($idTags[$tag]) && $idTags[$tag] !== '') {
                $tags[$tag] = $idTags[$tag];
            }
        }

        $remainingTags = array_diff_key($idTags, $tags);
        if (! $includeIdentifiers) {
            unset($remainingTags['uuid'], $remainingTags['cluster_uuid']);
        }

        ksort($remainingTags);

        $parts = [];
        foreach ($tags + $remainingTags as $tag => $value) {
            $parts[] = sprintf('%s=%s', $tag, $value);
        }

        if ($clusterName !== null) {
            $parts[] = sprintf('cluster=%s', $clusterName);
        }

        return implode(', ', $parts);
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\ProvidedHook\Notifications;

use Generator;
use Icinga\Module\Kubernetes\Web\Factory;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Notifications\Hook\ObjectsRendererHook;
use ipl\Html\Attributes;
use ipl\Html\FormattedString;
use ipl\Html\Html;
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

        return (new ResourceList(
            Factory::fetchResource($objectIdTag['resource'])
                ->filter(Filter::equal('uuid', Uuid::fromString($objectIdTag['uuid'])->getBytes()))
        ));
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
            if (! isset($idTags['resource']) || ! isset($idTags['name'])) {
                continue;
            }

            switch ($idTags['resource']) {
                case 'pod':
                    if (! $asHtml) {
                        yield $idTags => sprintf(
                            $this->translate('%s - %s', '<namespace> - <name>'),
                            $idTags['namespace'],
                            $idTags['name']
                        );
                    } else {
                        yield $idTags => Html::sprintf(
                            $this->translate('%s', '<pod>'),
                            [
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'namespace-badge']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                                    new Text($idTags['namespace'])
                                ),
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'subject']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
                                    new Text($idTags['name'])
                                )
                            ]
                        );
                    }

                    break;
                case 'deployment':
                    if (! $asHtml) {
                        yield $idTags => sprintf($this->translate('%s', '<deployment>'), $idTags['name']);
                    } else {
                        yield $idTags => Html::sprintf(
                            $this->translate('%s', '<deployment>'),
                            [
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'namespace-badge']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                                    new Text($idTags['namespace'])
                                ),
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'subject']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-deployment'])),
                                    new Text($idTags['name'])
                                )
                            ]
                        );
                    }

                    break;
                case 'node':
                    if (! $asHtml) {
                        yield $idTags => sprintf($this->translate('%s', '<node>'), $idTags['name']);
                    } else {
                        yield $idTags => Html::sprintf(
                            $this->translate('%s', '<node>'),
                            new HtmlElement(
                                'span',
                                new Attributes(['class' => 'subject']),
                                new Icon('share-nodes'),
                                new Text($idTags['name'])
                            )
                        );
                    }
                    break;
                case 'daemon_set':
                    if (! $asHtml) {
                        yield $idTags => sprintf($this->translate('%s', '<daemon_set>'), $idTags['name']);
                    } else {
                        yield $idTags => Html::sprintf(
                            $this->translate('%s', '<daemon_set>'),
                            [
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'namespace-badge']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                                    new Text($idTags['namespace'])
                                ),
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'subject']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-daemon-set'])),
                                    new Text($idTags['name'])
                                )
                            ]
                        );
                    }
                    break;
                case 'replica_set':
                    if (! $asHtml) {
                        yield $idTags => sprintf($this->translate('%s', '<replica_set>'), $idTags['name']);
                    } else {
                        yield $idTags => Html::sprintf(
                            $this->translate('%s', '<replica_set>'),
                            [
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'namespace-badge']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                                    new Text($idTags['namespace'])
                                ),
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'subject']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-replica-set'])),
                                    new Text($idTags['name'])
                                )
                            ]
                        );
                    }
                    break;
                case 'stateful_set':
                    if (! $asHtml) {
                        yield $idTags => sprintf($this->translate('%s', '<stateful_set>'), $idTags['name']);
                    } else {
                        yield $idTags => Html::sprintf(
                            $this->translate('%s is %s', '<stateful_set> is <icinga_state>'),
                            [
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'namespace-badge']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                                    new Text($idTags['namespace'])
                                ),
                                new HtmlElement(
                                    'span',
                                    new Attributes(['class' => 'subject']),
                                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-stateful-set'])),
                                    new Text($idTags['name'])
                                )
                            ]
                        );
                    }
            }
        }
    }
}

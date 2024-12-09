<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Web\Url;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use Ramsey\Uuid\Uuid;

class Environment extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'environment-widget'];

    /**
     * Create Environment nodes
     *
     * @param Model $currentObject The object whose parents and children should be shown
     * @param Query|null $parents The query for parents
     * @param Query|null $children The query for children
     * @param mixed|null $parentsFilter The filter for parents
     * @param mixed|null $childrenFilter The filter for children
     */
    public function __construct(
        protected Model $currentObject,
        protected ?Query $parents = null,
        protected ?Query $children = null,
        protected $parentsFilter = null,
        protected $childrenFilter = null,
    ) {
    }

    protected function assemble(): void
    {
        $parents = $this->getParentsAndChildrenKind()['parents'];
        $children = $this->getParentsAndChildrenKind()['children'];

        if ($this->parents !== null) {
            $parentList = $this->createNodeList(true);
            $svgParentList = new HtmlElement(
                'div',
                Attributes::create(['class' => 'parents']),
                $parentList,
                $this->createSVGLines($parentList->count(), true)
            );
        } else {
            $svgParentList = new HtmlElement(
                'div',
                null
            );
        }

        if ($this->children !== null) {
            $childList = $this->createNodeList();
            $svgChildrenList = new HtmlElement(
                'div',
                Attributes::create(['class' => 'children']),
                $this->createSVGLines($childList->count()),
                $childList
            );
        } else {
            $svgChildrenList = new HtmlElement(
                'div',
                null
            );
        }

        $this->addHtml(
            new HtmlElement(
                'div',
                Attributes::create(['class' => 'parents-label']),
                new HtmlElement('div', Attributes::create(['class' => 'label']), Text::create(t($parents))),
            ),
            new HtmlElement(
                'div',
                Attributes::create(['class' => 'children-label']),
                new HtmlElement('div', Attributes::create(['class' => 'label']), Text::create(t($children))),
            ),
            $svgParentList,
            new HtmlElement(
                'div',
                Attributes::create(['class' => 'current']),
                new HtmlElement(
                    'span',
                    Attributes::create(['class' => 'self']),
                    $this->renderNode($this->currentObject)
                )
            ),
            $svgChildrenList
        );
    }

    /**
     * Create the node list
     *
     * @param bool $forParents Whether the list is for parents or children
     *
     * @return HtmlElement
     */
    private function createNodeList(bool $forParents = false): HtmlElement
    {
        $list = new HtmlElement('ul', Attributes::create(['class' => 'node-list']));

        $query = $forParents ? $this->parents : $this->children;

        foreach ($query as $node) {
            $kind = Factory::getKindFromModel($node);
            $url = Factory::createUrl($kind);
            $url->addParams(['id' => (string) Uuid::fromBytes($node->uuid)]);

            $list->addHtml(
                new HtmlElement(
                    'li',
                    null,
                    new Link(
                        $this->renderNode($node),
                        $url,
                        Attributes::create(['class' => 'node', 'data-base-target' => '_next'])
                    )
                )
            );

            $this->createSummary($node, $list, $forParents);
        }

        return $list;
    }

    /**
     * Render the given node with link
     *
     * @param Model $node
     *
     * @return ValidHtml
     */
    private function renderNode(Model $node): ValidHtml
    {
        $subject = new HtmlElement('span', Attributes::create(['class' => 'subject']));

        $subject
            ->setHtmlContent(Text::create($node->name))
            ->addAttributes(['title' => $node->name]);

        $kind = Factory::getKindFromModel($node);

        $icon = Factory::createIcon($kind);

        if (
            $node instanceof Service || $node instanceof Ingress || $node instanceof CronJob
            || $node instanceof PersistentVolume || $node instanceof PersistentVolumeClaim
        ) {
            $mainBall = new StateBall('', StateBall::SIZE_LARGE);
        } else {
            $mainBall = new StateBall($node->icinga_state, StateBall::SIZE_LARGE);
        }

        $mainBall->add($icon);

        $iconImage = HtmlElement::create('div', ['class' => 'icon-image']);

        return (new HtmlDocument())->addHtml($mainBall, $iconImage, $subject);
    }

    /**
     * Create the SVG lines
     *
     * @param $lineCount int The count of lines to draw
     * @param $forParents bool Whether the svg path should be for parents or children
     *
     * @return HtmlElement
     */
    private function createSVGLines(int $lineCount, bool $forParents = false): HtmlElement
    {
        $path = $forParents
            ? 'M 0 %d C 50 %d 50 50 100 50'
            : 'M 0 50 C 50 50 50 %d 100 %d';

        /**
         * @var $pathCoordinates array<int, array<int>> The `key` contains the count of curves to draw.
         *
         * The Svg Element is a square with fixed width and has a viewBox attr `0 0 100 100`. It is centred vertically
         * to the parent element.
         *
         * When 4 (max.) dependency nodes are present, coordinate 0/100 begins exactly in the middle of
         * the first/last node, coordinate 50 is exactly the center of the parent element. This makes the curve
         * calculation easier.
         */
        $pathCoordinates = [
            0 => [],
            1 => [50],
            2 => [33, 66],
            3 => [17, 50, 83],
            4 => [1, 33, 66, 99]
        ];

        $svg = new HtmlElement('svg', Attributes::create(['viewBox' => '0 0 100 100', 'class' => 'svg-lines']));
        foreach ($pathCoordinates[$lineCount] as $coordinate) {
            $svg->addHtml(
                new HtmlElement(
                    'path',
                    Attributes::create([
                                           'stroke'       => 'black',
                                           'stroke-width' => 1,
                                           'fill'         => 'none',
                                           'd'            => sprintf($path, $coordinate, $coordinate)
                                       ])
                )
            );
        }

        return $svg;
    }

    /**
     * Get the kind of parents and children
     *
     * @return string[]
     */
    private function getParentsAndChildrenKind(): array
    {
        return match (true) {
            $this->currentObject instanceof CronJob               => [
                'parents'  => '',
                'children' => 'Jobs'
            ],
            $this->currentObject instanceof DaemonSet
            || $this->currentObject instanceof Job
            || $this->currentObject instanceof StatefulSet        =>
            [
                'parents'  => '',
                'children' => 'Pods'
            ],
            $this->currentObject instanceof Deployment            => [
                'parents'  => '',
                'children' => 'Replica Sets'
            ],
            $this->currentObject instanceof Ingress               => [
                'parents'  => '',
                'children' => 'Services'
            ],
            $this->currentObject instanceof PersistentVolumeClaim => [
                'parents'  => 'Persistent Volumes',
                'children' => 'Pods',
            ],
            $this->currentObject instanceof PersistentVolume      => [
                'parents'  => '',
                'children' => 'Persistent Volume Claims',
            ],
            $this->currentObject instanceof Pod                   => [
                'parents'  => 'Services',
                'children' => 'Pod Owner',
            ],
            $this->currentObject instanceof ReplicaSet            => [
                'parents'  => 'Deployments',
                'children' => 'Pods'
            ],
            $this->currentObject instanceof Service               => [
                'parents'  => 'Ingresses',
                'children' => 'Pods'
            ],
            default                                               => [
                'parents'  => '',
                'children' => '',
            ]
        };
    }

    /**
     * Create summary node
     *
     * @param Model $node
     * @param HtmlElement $list
     * @param bool $forParents
     *
     * @return void
     */
    private function createSummary(Model $node, HtmlElement $list, bool $forParents = false): void
    {
        $kind = Factory::getKindFromModel($node);

        $kindPlural = $kind === 'ingress' ? $kind . 'es' : $kind . 's';

        $url = Url::fromPath("kubernetes/$kindPlural");

        $summary = new HtmlElement('footer');


        if ($this->currentObject instanceof Job && $node instanceof Pod) {
            $pods = (new ItemCountIndicator())
                ->addIndicator('critical', $this->currentObject->failed)
                ->addIndicator('pending', $this->currentObject->active)
                ->addIndicator('ok', $this->currentObject->succeeded);

            $summary->addHtml(
                (new HorizontalKeyValue(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
                    $pods
                ))
                    ->addAttributes([
                        'title' => sprintf(
                            $this->translate(
                                '%d %s available (%d not available)',
                                '%d:num_of_available_pods %s:pods_translation (%d:num_of_unavailable_pods)'
                            ),
                            $pods->getIndicator('ok'),
                            $this->translatePlural('pod', 'pods', $pods->getIndicator('ok')),
                            $pods->getIndicator('critical')
                        )
                    ]),
            );
        } elseif (
            ($this->currentObject instanceof ReplicaSet || $this->currentObject instanceof StatefulSet)
            && $node instanceof Pod
        ) {
            $pods = (new ItemCountIndicator())
                ->addIndicator(
                    'critical',
                    $this->currentObject->actual_replicas - $this->currentObject->available_replicas
                )
                ->addIndicator(
                    'pending',
                    $this->currentObject->desired_replicas - $this->currentObject->actual_replicas
                )
                ->addIndicator('ok', $this->currentObject->available_replicas);

            $summary->addHtml(
                (new HorizontalKeyValue(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
                    $pods
                ))
                    ->addAttributes([
                        'title' => sprintf(
                            $this->translate(
                                '%d %s available (%d unavailable)',
                                '%d:num_of_available_replicas %s:replicas_translation 
                                (%d:num_of_unavailable_replicas)'
                            ),
                            $pods->getIndicator('ok'),
                            $this->translatePlural('replica', 'replicas', $pods->getIndicator('ok')),
                            $pods->getIndicator('critical')
                        )
                    ]),
            );
        } elseif ($this->currentObject instanceof DaemonSet && $node instanceof Pod) {
            $pods = (new ItemCountIndicator())
                ->addIndicator('critical', $this->currentObject->number_unavailable)
                ->addIndicator(
                    'pending',
                    $this->currentObject->desired_number_scheduled - $this->currentObject->current_number_scheduled
                )
                ->addIndicator('ok', $this->currentObject->number_available);

            $summary->addHtml(
                (new HorizontalKeyValue(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-pod'])),
                    $pods
                ))
                    ->addAttributes([
                        'title' => sprintf(
                            $this->translate(
                                '%d %s available (%d unavailable)',
                                '%d:num_of_available_daemon_pods %s:daemon_pods_translation 
                                (%d:num_of_unavailable_daemon_pods)'
                            ),
                            $pods->getIndicator('ok'),
                            $this->translatePlural('daemon pod', 'daemon pods', $pods->getIndicator('ok')),
                            $pods->getIndicator('critical')
                        )
                    ]),
            );
        } else {
            $content = $forParents ? new Text($this->parents->count()) : new Text($this->children->count());
            $summary->addHtml(
                new HorizontalKeyValue(
                    new HtmlElement('i', new Attributes(['class' => "icon kicon-$kind"])),
                    $content
                )
            );
        }

        if ($list->count() > 2) {
            if ($forParents) {
                $list->addHtml(
                    new HtmlElement(
                        'li',
                        null,
                        new Link(
                            $summary,
                            $url->setFilter($this->parentsFilter),
                            Attributes::create(['class' => ['summary']])
                        )
                    )
                );
            } else {
                $list->addHtml(
                    new HtmlElement(
                        'li',
                        null,
                        new Link(
                            $summary,
                            $url->setFilter($this->childrenFilter),
                            Attributes::create(['class' => ['summary']])
                        )
                    )
                );
            }
        }
    }
}

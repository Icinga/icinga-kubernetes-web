<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\EmptyState;
use Ramsey\Uuid\Uuid;

abstract class BaseItemList extends BaseHtmlElement
{
    use BaseFilter;
    use Translation;
    use ViewMode;

    /**
     * Indicates whether the item list should be treated as an action list.
     *
     * @var bool $actionList
     */
    protected bool $actionList = true;

    protected iterable $query;

    protected array $baseAttributes = [
        'class'                         => 'item-list',
        'data-base-target'              => '_next',
        'data-pdfexport-page-breaks-at' => '.list-item'
    ];

    protected $tag = 'ul';

    public function __construct(iterable $query)
    {
        $this->query = $query;

        $this->init();
    }

    /**
     * Enable or disable the action list functionality by setting the $actionList
     * property.
     *
     * @param bool $actionList
     *
     * @return static
     */
    public function setActionList(bool $actionList): static
    {
        $this->actionList = $actionList;

        return $this;
    }

    /**
     * Initialize the item list
     * If you want to adjust the item list after construction, override this method.
     */
    protected function init(): void
    {
    }

    protected function assemble(): void
    {
        $detailUrlAdded = ! $this->actionList;
        $itemClass = $this->getItemClass();

        $this->addAttributes($this->baseAttributes);
        $this->addAttributes(['class' => $this->viewMode]);
        foreach ($this->query as $item) {
            if (! $detailUrlAdded) {
                $this->addAttributes(['class' => 'action-list'] + [
                        'data-icinga-detail-url' => Url::fromPath(
                            'kubernetes/' . str_replace('_', '-', $item->getTableAlias())
                        )
                    ]);
                $detailUrlAdded = true;
            }

            $listItem = (new $itemClass($item, $this))
                ->addAttributes([
                    'data-action-item'          => true,
                    'data-icinga-detail-filter' => QueryString::render(
                        Filter::equal('id', Uuid::fromBytes($item->uuid)->toString())
                    )
                ]);

            if ($this->viewMode !== null) {
                $listItem->setViewMode($this->viewMode);
            }

            $this->addHtml(
                $listItem
            );
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyState($this->translate('No items to display.')));
        }
    }

    abstract protected function getItemClass(): string;
}

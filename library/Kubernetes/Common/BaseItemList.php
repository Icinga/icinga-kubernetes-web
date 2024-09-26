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

    protected array $baseAttributes = [
        'class'                         => 'item-list action-list',
        'data-base-target'              => '_next',
        'data-pdfexport-page-breaks-at' => '.list-item'
    ];

    protected iterable $query;

    protected $tag = 'ul';

    public function __construct(iterable $query)
    {
        $this->query = $query;

        $this->init();
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
        $detailUrlAdded = false;
        $itemClass = $this->getItemClass();

        foreach ($this->query as $item) {
            if (! $detailUrlAdded) {
                $this->addAttributes($this->baseAttributes + [
                        'data-icinga-detail-url' => Url::fromPath(
                            'kubernetes/' . str_replace('_', '-', $item->getTableAlias())
                        )
                    ]);
                $detailUrlAdded = true;
            }

            $this->addHtml(
                (new $itemClass($item, $this))
                    ->addAttributes([
                        'data-action-item'          => true,
                        'data-icinga-detail-filter' => QueryString::render(
                            Filter::equal('id', Uuid::fromBytes($item->uuid)->toString())
                        )
                    ])
            );
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyState($this->translate('No items to display.')));
        }
    }

    abstract protected function getItemClass(): string;
}

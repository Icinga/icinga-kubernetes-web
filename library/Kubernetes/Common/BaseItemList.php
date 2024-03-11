<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;
use ipl\Stdlib\BaseFilter;
use ipl\Web\Widget\EmptyState;

abstract class BaseItemList extends BaseHtmlElement
{
    use BaseFilter;
    use Translation;

    protected $baseAttributes = [
        'class'                         => 'item-list',
        'data-base-target'              => '_next',
        'data-pdfexport-page-breaks-at' => '.list-item'
    ];

    /** @var iterable */
    protected $data;

    protected $tag = 'ul';

    /**
     * Create a new item  list
     *
     * @param iterable $data Data source of the list
     */
    public function __construct($data)
    {
        if (! is_iterable($data)) {
            throw new InvalidArgumentException('Data must be an array or an instance of Traversable');
        }

        $this->data = $data;

        $this->addAttributes($this->baseAttributes);
        $this->getAttributes()
            ->registerAttributeCallback('class', function () {
                return 'action-list';
            });

        $this->init();
    }

    /**
     * Initialize the item list
     * If you want to adjust the item list after construction, override this method.
     */
    protected function init(): void
    {
    }

    protected function assemble()
    {
        $itemClass = $this->getItemClass();

        foreach ($this->data as $data) {
            /** @var BaseListItem $item */
            $item = new $itemClass($data, $this);

            $this->add($item);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->add(new EmptyState($this->translate('No items to display.')));
        }
    }

    abstract protected function getItemClass(): string;
}

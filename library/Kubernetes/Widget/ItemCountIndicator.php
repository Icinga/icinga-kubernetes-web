<?php

namespace Icinga\Module\Kubernetes\Widget;

use Icinga\Module\Kubernetes\Model\Container;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class ItemCountIndicator extends BaseHtmlElement
{
    const DEFAULT_STYLE = 'solid'; // plus 'outline' or 'hexagon'

    const MAX_ITEMS = 10;
    const NARROW_THRESHOLD = 5;
    const DEFAULT_ITEM_TYPE = 'items';

    protected $style;

    protected $items;

    protected $defaultAttributes = ['class' => 'item-count-indicator'];

    protected $tag = 'ul';

    public function __construct($items = [], $style = self::DEFAULT_STYLE)
    {
        $this->items = $items;
        $this->style = $style;
    }

    public function addItem($state, $title = null) {
        $this->items []= (object) [ 'state' => $state, 'title' => $title ];
    }

    protected function assemble()
    {
        $itemCount = count($this->items);

        if ($itemCount > self::MAX_ITEMS ) {
            $this->tag = 'span';
            $this->add($itemCount);
        } else {
            if ($this->style != 'hexagon' && $itemCount > self::NARROW_THRESHOLD - 1) {
                $this->addAttributes(['class' => 'narrow']);
            }

            $this->addAttributes(['class' => $this->style]);

            foreach ($this->items as $item) {
                switch ($item->state) {
                    case Container::STATE_RUNNING:
                        $state = 'ok';

                        break;
                    case Container::STATE_TERMINATED:
                        $state = 'unknown';

                        break;
                    case Container::STATE_WAITING:
                        $state = 'warning';

                        break;
                    default:
                        $state = 'unknown';
                }
                $this->add(new HtmlElement('li', Attributes::create(['class' => $state])));
            }
        }
    }
}
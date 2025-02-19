<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Web\Factory;
use Icinga\Module\Kubernetes\Web\MoveFavoriteForm;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Icon;
use Ramsey\Uuid\Uuid;

/**
 * Base class for list items
 */
abstract class BaseListItem extends BaseHtmlElement
{
    use ViewMode;

    protected array $baseAttributes = ['class' => 'list-item'];

    protected $item;

    protected BaseItemList $list;

    protected $tag = 'li';

    /**
     * Create a new list item
     *
     * @param object       $item
     * @param BaseItemList $list
     */
    public function __construct($item, BaseItemList $list)
    {
        $this->item = $item;
        $this->list = $list;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    /**
     * Initialize the list item
     * If you want to adjust the list item after construction, override this method.
     */
    protected function init(): void
    {
    }

    protected function assemble(): void
    {
        $draggable = isset($this->item->favorite->priority);
        if ($draggable) {
            $this->add(
                (new MoveFavoriteForm())
                    ->setAction(
                        Links::moveFavorite(Factory::canonicalizeKind($this->item->getTableAlias()))->getAbsoluteUrl()
                    )
                    ->populate([
                        'uuid'     => Uuid::fromBytes($this->item->uuid)->toString(),
                        'priority' => $this->item->favorite->priority,
                    ])
                    ->setAttribute('data-base-target', '_self'),
            );
            // TODO wait for feedback how to style
//            $div = Html::tag('div', Attributes::create(['class' => 'vertical-align']));
//            $div->addHtml(new Icon('bars', ['data-drag-initiator' => true]));
//            $this->add($div);
        }
        $this->add([
            $this->createVisual(),
            $this->createMain()
        ]);
        if ($draggable) {
            $div = Html::tag('div', Attributes::create(['class' => 'vertical-align']));
            $div->addHtml(new Icon('bars', ['data-drag-initiator' => true]));
            $this->add($div);
        }
    }

    protected function createVisual(): ?BaseHtmlElement
    {
        $visual = Html::tag('div', ['class' => 'visual']);

        $this->assembleVisual($visual);

        return $visual;
    }

    protected function createMain(): BaseHtmlElement
    {
        $main = Html::tag('div', ['class' => 'main']);

        $this->assembleMain($main);

        return $main;
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
    }

    abstract protected function assembleMain(BaseHtmlElement $main): void;

    protected function createCaption(): BaseHtmlElement
    {
        $caption = Html::tag('section', ['class' => 'caption']);

        $this->assembleCaption($caption);

        return $caption;
    }

    protected function assembleCaption(BaseHtmlElement $caption)
    {
    }

    protected function createFooter(): BaseHtmlElement
    {
        $footer = new HtmlElement('footer');

        $this->assembleFooter($footer);

        return $footer;
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
    }

    protected function createHeader(): BaseHtmlElement
    {
        $header = Html::tag('header');

        $this->assembleHeader($header);

        return $header;
    }

    abstract protected function assembleHeader(BaseHtmlElement $header): void;

    protected function createTitle(): BaseHtmlElement
    {
        $title = HTML::tag('div', ['class' => 'title']);

        $this->assembleTitle($title);

        return $title;
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Controls;

use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Forms\FavorForm;
use Icinga\Module\Kubernetes\Forms\UnfavorForm;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Orm\Model;

class QuickActions extends BaseHtmlElement
{
    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'quick-actions'];

    public function __construct(
        protected Model $item,
        protected ?Model $favorite = null
    ) {
    }

    protected function assemble()
    {
        if ($this->favorite !== null) {
            $this->add(
                Html::tag(
                    'li',
                    (new UnfavorForm())->setAction(
                        Links::unfavor($this->item->uuid)->getAbsoluteUrl()
                    )
                )
            );
        } else {
            $this->add(
                Html::tag(
                    'li',
                    (new FavorForm())->setAction(
                        Links::favor($this->item->uuid, Factory::getKindFromModel($this->item))->getAbsoluteUrl()
                    )
                )
            );
        }
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Forms\FavorForm;
use Icinga\Module\Kubernetes\Forms\UnfavorForm;
use ipl\Html\BaseHtmlElement;
use ipl\Orm\Model;

class QuickActions extends BaseHtmlElement
{
    protected Model $item;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'quick-actions'];

    public function __construct()
    {
    }

    protected function assemble()
    {
    }
}

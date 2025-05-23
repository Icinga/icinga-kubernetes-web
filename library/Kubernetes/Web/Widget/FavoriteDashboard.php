<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

use Icinga\Web\Widget\Dashboard;
use ipl\Html\BaseHtmlElement;

class FavoriteDashboard extends BaseHtmlElement
{
    /** @var Dashboard The dashboard to show all favorites of all resources. */
    protected Dashboard $dashboard;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'favorite-dashboard'];

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    public function assemble()
    {
        foreach ($this->dashboard->getActivePane()->getDashlets() as $dashlet) {
            $this->add(new FavoriteDashlet($dashlet));
        }
    }
}

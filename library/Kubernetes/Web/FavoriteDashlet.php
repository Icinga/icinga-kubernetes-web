<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Web\Widget\Dashboard\Dashlet;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class FavoriteDashlet extends BaseHtmlElement
{
    /** @var Dashlet The dashlet to show all favorites of a resource. */
    protected Dashlet $dashlet;

    protected $tag = 'div';

    public function __construct(Dashlet $dashlet)
    {
        $this->dashlet = $dashlet;
    }

    public function assemble()
    {
        $url = $this->dashlet->getUrl()->setParam('showCompact');
        $fullUrl = $url->getUrlWithout(['showCompact', 'view']);
        $tooltip = sprintf('Show %s', $this->dashlet->getTitle());
        $progressLabel = $this->dashlet->getProgressLabe();

        $this->addHtml(
            Html::tag(
                'h1',
                null,
                Html::tag(
                    'a',
                    Attributes::create([
                        'href'             => $fullUrl,
                        'aria-label'       => $tooltip,
                        'title'            => $tooltip,
                        'data-base-target' => 'col1'
                    ]),
                    $this->dashlet->getName()
                )
            )
        );

        $this->addHtml(
            Html::tag(
                'div',
                Attributes::create([
                    'class'               => 'container',
                    'data-last-update'    => -1,
                    'data-icinga-refresh' => 60,
                    'data-icinga-url'     => $url
                ]),
                Html::tag(
                    'p',
                    Attributes::create(['class' => 'progress-label']),
                    [
                        $progressLabel,
                        Html::tag('span', null, '.'),
                        Html::tag('span', null, '.'),
                        Html::tag('span', null, '.')
                    ]
                )
            )
        );
    }
}

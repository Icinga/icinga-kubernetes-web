<?php

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Web\Factory;
use Icinga\Module\Kubernetes\Web\FavoriteToggleForm;
use Icinga\Module\Kubernetes\Web\ViewModeSwitcher;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\StateBall;

trait DefaultListItemVisual
{
    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $size = match ($this->getViewMode()) {
            ViewModeSwitcher::VIEW_MODE_MINIMAL,
            ViewModeSwitcher::VIEW_MODE_COMMON   => 'sm',
            ViewModeSwitcher::VIEW_MODE_DETAILED => 'm',
        };

        $visual->addHtml(new StateBall($this->item->icinga_state, $size));

        if ($this->getViewMode() === ViewModeSwitcher::VIEW_MODE_MINIMAL) {
            return;
        }

        $rs = Favorite::on(Database::connection())
            ->filter(Filter::all(
                Filter::equal('resource_uuid', $this->item->uuid),
                Filter::equal('username', Auth::getInstance()->getUser()->getUsername())
            ))
            ->execute();

        $visual->addHtml((new FavoriteToggleForm($rs->hasResult()))
            ->setAction(Links::toggleFavorite(
                $this->item->uuid,
                Factory::canonicalizeKind($this->item->getTableAlias())
            )->getAbsoluteUrl())
            ->setAttribute('class', sprintf("favorite-toggle favorite-toggle-$size"))
            ->setAttribute('data-base-target', '_self')
        );
    }
}

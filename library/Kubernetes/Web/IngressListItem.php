<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\BaseListItem;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\DefaultListItemHeader;
use Icinga\Module\Kubernetes\Common\DefaultListItemMain;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Favorite;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

class IngressListItem extends BaseListItem
{
    use Translation;
    use DefaultListItemHeader;
    use DefaultListItemMain;

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        // TODO add state reason then replace function by DefaultListItemCaption trait
        $caption->addHtml(new Text('Placeholder for Icinga State Reason'));
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $hosts = [];
        foreach ($this->item->ingress_rule as $rule) {
            if ($rule->host !== null) {
                $hosts[] = $rule->host;
            }
        }

        $footer->addHtml(
            new HorizontalKeyValue(
                $this->translate('Host'),
                ! empty($hosts) ? implode(', ', $hosts) : '-'
            )
        );
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(
            new HtmlElement(
                'span',
                new Attributes(['class' => 'namespace-badge']),
                new HtmlElement('i', new Attributes(['class' => 'icon kicon-namespace'])),
                new Text($this->item->namespace)
            ),
            new Link(
                (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-ingress'])),
                    new Text($this->item->name)
                ),
                Links::ingress($this->item),
                new Attributes(['class' => 'subject'])
            )
        );
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $size = match ($this->getViewMode()) {
            ViewModeSwitcher::VIEW_MODE_MINIMAL,
            ViewModeSwitcher::VIEW_MODE_COMMON   => 'sm',
            ViewModeSwitcher::VIEW_MODE_DETAILED => StateBall::SIZE_MEDIUM,
        };

        // TODO add icinga state then replace function by DefaultListItemVisual trait
        $visual->addHtml(new StateBall('none', $size));

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

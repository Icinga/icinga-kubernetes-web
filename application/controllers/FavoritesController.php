<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\Factory;
use Icinga\Module\Kubernetes\Web\FavoriteDashboard;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Dashboard\Dashlet;
use Icinga\Web\Widget\Dashboard\Pane;
use ipl\Stdlib\Filter;

class FavoritesController extends Controller
{
    const array FAVORABLE_KINDS = [
        'cronjob',
        'daemonset',
        'deployment',
        'ingress',
        'job',
        'namespace',
        'node',
        'persistentvolumeclaim',
        'persistentvolume',
        'pod',
        'replicaset',
        'service',
        'statefulset'
    ];

    public function indexAction(): void
    {
        $this->addTitleTab('Favorites');
        $dashboard = new Dashboard();
        $pane = (new Pane('favorites'))->setTitle('Favorites');
        $dashboard->addPane($pane);

        foreach (self::FAVORABLE_KINDS as $kind) {
            $hasFavorites = Favorite::on(Database::connection())->filter(
                    Filter::all(
                        Filter::equal('kind', $kind),
                        Filter::equal('username', Auth::getInstance()->getUser()->getUsername())
                    )
                )->first() !== null;
            if ($hasFavorites) {
                $dashlet = new Dashlet(Factory::createTitle($kind), Factory::createListUrl($kind) . '?view=minimal&show-favorites=y&sort=favorite.priority desc', $pane);
                $pane->addDashlet($dashlet);
            }
        }

        $dashboard->activate('favorites');
        $this->addContent(new FavoriteDashboard($dashboard));
    }
}

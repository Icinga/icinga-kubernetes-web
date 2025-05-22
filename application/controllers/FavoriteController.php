<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Application\Logger;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Forms\FavorForm;
use Icinga\Module\Kubernetes\Forms\UnfavorForm;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Web\Controller;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use Throwable;

class FavoriteController extends Controller
{
    /**
     * Favors a resource by adding it into the database. Throws an exception if the database operation fails.
     *
     * @return void
     */
    public function favorAction(): void
    {
        (new FavorForm())
            ->on(FavorForm::ON_SUCCESS, function () {
                $db = Database::connection();
                $uuid = $this->params->get('uuid');
                $kind = $this->params->get('kind');
                $username = Auth::getInstance()->getUser()->getUsername();

                try {
                    $highestPriorityFavorite = Favorite::on($db)
                        ->columns('priority')
                        ->filter(
                            Filter::all(
                                Filter::equal('kind', $kind),
                                Filter::equal('username', $username)
                            )
                        )
                        ->orderBy('priority', SORT_DESC)
                        ->first();

                    $db->insert(
                        'favorite',
                        [
                            'resource_uuid' => $uuid,
                            'kind'          => $kind,
                            'username'      => $username,
                            'priority'      => ($highestPriorityFavorite?->priority ?? -1) + 1,
                        ]
                    );
                } catch (Throwable $e) {
                    Logger::error($e);
                    Logger::error($e->getTraceAsString());

                    throw $e;
                }
            })
            ->handleRequest($this->getServerRequest());

        $this->closeModalAndRefreshRemainingViews('__REFRESH__');
    }

    /**
     * Unfavors a resource by removing it from the database. Throws an exception if the database operation fails.
     *
     * @return void
     */
    public function unfavorAction(): void
    {
        (new UnfavorForm())
            ->on(FavorForm::ON_SUCCESS, function () {
                $db = Database::connection();
                $uuid = $this->params->get('uuid');
                $username = Auth::getInstance()->getUser()->getUsername();
                try {
                    $transactionStarted = false;
                    if (! $db->inTransaction()) {
                        $transactionStarted = true;
                        $db->beginTransaction();
                    }

                    $favoriteToDelete = Favorite::on($db)
                        ->filter(Filter::all(
                            Filter::equal('resource_uuid', $uuid),
                            Filter::equal('username', $username)
                        ))
                        ->first();

                    $db->delete(
                        'favorite',
                        [
                            'resource_uuid = ?' => $uuid,
                            'username = ?'      => $username,
                        ]
                    );

                    $affectedFavorites = Favorite::on($db)
                        ->columns(['resource_uuid', 'username'])
                        ->filter(
                            Filter::all(
                                Filter::equal('kind', $favoriteToDelete->kind),
                                Filter::equal('username', $username),
                                Filter::greaterThan('priority', $favoriteToDelete->priority)
                            )
                        )
                        ->orderBy('priority', SORT_ASC);

                    foreach ($affectedFavorites as $favorite) {
                        $db->update(
                            'favorite',
                            ['priority' => new Expression('priority - 1')],
                            ['resource_uuid = ?' => $favorite->resource_uuid, 'username = ?' => $favorite->username]
                        );
                    }

                    if ($transactionStarted) {
                        $db->commitTransaction();
                    }
                } catch (Throwable $e) {
                    Logger::error($e);
                    Logger::error($e->getTraceAsString());

                    $db->rollBackTransaction();

                    throw $e;
                }
            })
            ->handleRequest($this->getServerRequest());

        $this->closeModalAndRefreshRemainingViews('__REFRESH__');
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Application\Logger;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Favorite;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\FavoriteToggleForm;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;
use Throwable;

class FavoriteController extends Controller
{
    /**
     * Toggles the favorite status of a resource. If the resource is already a favorite,
     * it will be removed from the favorites list. If it is not a favorite yet, it will be
     * added to the favorites list. Throws an exception if the database operation fails.
     *
     * @return void
     */
    public function toggleAction(): void
    {
        (new FavoriteToggleForm(true))
            ->on(FavoriteToggleForm::ON_SUCCESS, function (FavoriteToggleForm $form) {
                $db = Database::connection();
                $uuid = Uuid::fromString($this->params->get('uuid'))->getBytes();
                $kind = $this->params->get('kind');
                $username = Auth::getInstance()->getUser()->getUsername();
                $checked = $form->getValue('favorite-checkbox');

                try {
                    if ($checked) {
                        $highestPriorityFavorite = Favorite::on($db)
                            ->columns('priority')
                            ->filter(Filter::all(
                                Filter::equal('kind', $kind),
                            ))
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
                    } else {
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
                            ->filter(Filter::equal('kind', $favoriteToDelete->kind))
                            ->filter(Filter::greaterThan('priority', $favoriteToDelete->priority))
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
                    }
                } catch (Throwable $e) {
                    Logger::error($e);
                    Logger::error($e->getTraceAsString());

                    throw $e;
                }

                // Suppress handling XHR response and disable view rendering,
                // so we can use the form in the list without the page reloading.
                $this->getResponse()->setHeader('X-Icinga-Container', 'ignore', true);
                $this->_helper->viewRenderer->setNoRender();
            })->handleRequest($this->getServerRequest());
    }
}

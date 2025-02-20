<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Application\Logger;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Forms\FavorForm;
use Icinga\Module\Kubernetes\Forms\UnfavorForm;
use Icinga\Module\Kubernetes\Web\Controller;
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
                    $db->insert(
                        'favorite',
                        [
                            'resource_uuid' => $uuid,
                            'kind'          => $kind,
                            'username'      => $username,
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
                    $db->delete(
                        'favorite',
                        [
                            'resource_uuid = ?' => $uuid,
                            'username = ?'      => $username,
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
}

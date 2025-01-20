<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Application\Logger;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\FavoriteToggleForm;
use Ramsey\Uuid\Uuid;
use Throwable;

class FavoriteController extends Controller
{
    public function toggleAction(): void
    {
        (new FavoriteToggleForm(true))
            ->on(FavoriteToggleForm::ON_SUCCESS, function (FavoriteToggleForm $form) {
                $uuid = $this->params->get('uuid');
                $kind = $this->params->get('kind');
                $checked = $form->getValue('favorite-checkbox');
                try {
                    if ($checked) {
                        Database::connection()->insert(
                            'favorite',
                            [
                                'resource_uuid' => Uuid::fromString($uuid)->getBytes(),
                                'kind'          => $kind,
                                'username'      => Auth::getInstance()->getUser()->getUsername(),
                            ]
                        );
                    } else {
                        Database::connection()->delete(
                            'favorite',
                            [
                                'resource_uuid = ?' => Uuid::fromString($uuid)->getBytes(),
                                'username = ?'      => Auth::getInstance()->getUser()->getUsername(),
                            ]
                        );
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

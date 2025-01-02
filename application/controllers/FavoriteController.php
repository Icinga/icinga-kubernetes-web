<?php

/* Icinga Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Web\Controller;
use Icinga\Module\Kubernetes\Web\FavoriteToggleForm;
use Ramsey\Uuid\Uuid;

class FavoriteController extends Controller
{
    public function toggleAction(): void
    {
        (new FavoriteToggleForm(true))
            ->on(FavoriteToggleForm::ON_SUCCESS, function (FavoriteToggleForm $form) {
                $uuid = $this->params->get('uuid');
                $checked = $form->getValue('favorite-checkbox');
                try {
                    if ($checked) {
                        Database::connection()->insert(
                            'favorite',
                            [
                                'resource_uuid' => Uuid::fromString($uuid)->getBytes(),
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
                } catch (\Exception $e) {
                    die($e->getMessage());
                }
                $this->getResponse()->setHeader('X-Icinga-Container', 'ignore', true)->sendResponse();
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();
            })->handleRequest($this->getServerRequest());
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\ProvidedHook;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Hook\ApplicationStateHook;
use Icinga\Application\Logger;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Config;
use Icinga\Module\Notifications\Forms\SourceForm;
use ipl\Stdlib\Filter;
use Throwable;

class AutoPopulateSource extends ApplicationStateHook
{
    protected const DEFAULT_SOURCE_USER = 'Icinga for Kubernetes';

    public function collectMessages()
    {
        $config = \Icinga\Application\Config::module('kubernetes');
        if (! $config->get('settings', 'auto_create_notifications_source', false)) {
            return;
        }

        $config = Config::on(Database::connection());
        $config->filter(
            Filter::any(
                Filter::equal('key', 'notifications.username'),
                Filter::equal('key', 'notifications.password'),
                Filter::equal('key', 'notifications.source_id')
            )
        );

        $username = null;
        $password = null;
        $sourceID = null;
        foreach ($config as $pair) {
            if ($pair->key === 'notifications.username') {
                $username = $pair->value;
            }
            if ($pair->key === 'notifications.password') {
                $password = $pair->value;
            }
            if ($pair->key === 'notifications.source_id') {
                $sourceID = (int)$pair->value;
            }
        }

        $sourceForm = new class (\Icinga\Module\Notifications\Common\Database::get()) extends SourceForm {
            public function hasBeenSubmitted()
            {
                return $this->hasBeenSent(); // Cheating :)
            }
        };

        if ($sourceID !== null) {
            $sourceForm->loadSource($sourceID);
        }

        if ($password !== null && $username == static::DEFAULT_SOURCE_USER) {
            $data = [
                'listener_password'      => $password,
                'listener_password_dupe' => $password,
            ];

            if ($sourceID === null) {
                $data ['name'] = static::DEFAULT_SOURCE_USER;
                $data ['type'] = 'kubernetes';
                $data ['icinga2_insecure_tls'] = 'n';
            }

            $sourceForm
                ->populate($data)
                ->on(SourceForm::ON_SUCCESS, function (SourceForm $form) use ($sourceID) {
                    if ($sourceID !== null) {
                        try {
                            $form->editSource();
                        } catch (Throwable $err) {
                            $this->addError(
                                'kubernetes.source.error',
                                time(),
                                'Failed to automatically update Icinga for Kubernetes notifications source'
                                . ', see logs for details!'
                            );

                            Logger::error(
                                'Failed to automatically update Icinga for Kubernetes notifications source: %s',
                                $err
                            );
                            Logger::debug($err->getTraceAsString());
                        }
                    } else {
                        try {
                            $form->addSource();
                        } catch (Throwable $err) {
                            $this->addError(
                                'kubernetes.source.error',
                                time(),
                                'Failed to automatically populate Icinga for Kubernetes notifications source'
                                . ', see logs for details!'
                            );

                            Logger::error(
                                'Failed to automatically populate Icinga for Kubernetes notifications source: %s',
                                $err
                            );
                            Logger::debug($err->getTraceAsString());

                            return;
                        }

                        try {
                            $sourceId = \Icinga\Module\Notifications\Common\Database::get()->lastInsertid();
                            $db = Database::connection();
                            $db->insert('config', [
                                $db->quoteIdentifier('key') => 'notifications.source_id',
                                'value'                     => $sourceId,
                            ]);
                        } catch (Throwable $err) {
                            $this->addError(
                                'kubernetes.source.error',
                                time(),
                                'Failed to insert Icinga for Kubernetes notifications source ID, see logs for details!'
                            );

                            Logger::error('Failed to insert Icinga for Kubernetes notifications source ID: %s', $err);
                            Logger::debug($err->getTraceAsString());
                        }
                    }
                });
        } elseif ($sourceID !== null && $password === null) {
            $sourceForm
                ->on(SourceForm::ON_SUCCESS, function (SourceForm $form) {
                    try {
                        $form->removeSource();
                    } catch (Throwable $err) {
                        $this->addError(
                            'kubernetes.source.error',
                            time(),
                            'Failed to remove auto generated Icinga for Kubernetes notifications source'
                            . ', see logs for details!'
                        );

                        Logger::error(
                            'Failed to remove auto generated Icinga for Kubernetes notifications source: %s',
                            $err
                        );
                        Logger::debug($err->getTraceAsString());
                    }

                    try {
                        $db = Database::connection();
                        $db->delete('config', ["{$db->quoteIdentifier('key')} = ?" => 'notifications.source_id']);
                    } catch (Throwable $err) {
                        $this->addError(
                            'kubernetes.source.error',
                            time(),
                            'Failed to delete Icinga for Kubernetes notifications source ID, see logs for details!'
                        );

                        Logger::error('Failed to delete Icinga for Kubernetes notifications source ID: %s', $err);
                        Logger::debug($err->getTraceAsString());
                    }
                });
        }

        // Again cheating to match the server request and our source form method.
        $orgRequestMethod = $_SERVER['REQUEST_METHOD'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = ServerRequest::fromGlobals();
        $_SERVER['REQUEST_METHOD'] = $orgRequestMethod;

        try {
            $sourceForm->ensureAssembled();
            $csrf = $sourceForm->getElement('CSRFToken');
            if (preg_match('/ value="([^"]+)/', $csrf->getAttributes()->render(), $matches)) {
                // CSRF token validation was changed in the meantime, so that it always triggers the validation,
                // even without a populated token, so we need to workaround it here.
                $csrf->setValue($matches[1]);
            }

            $sourceForm->handleRequest($request);
        } catch (Throwable $err) {
            $this->addError('kubernetes.source.error', time(), $err->getMessage());

            Logger::error($err);
            Logger::debug($err->getTraceAsString());
        }
    }
}

<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\NotificationsDiscovery;
use Icinga\Module\Kubernetes\Common\NotificationsUrl;
use Icinga\Module\Kubernetes\Model\Cluster;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Kubernetes\Web\Widget\ClusterForm;
use ipl\Stdlib\Filter;
use ipl\Validator\CallbackValidator;
use ipl\Web\Common\CalloutType;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Widget\Callout;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * Cluster-specific Notifications transport configuration
 */
class NotificationsConfigForm extends CompatForm
{
    protected ?string $discoveryConfirmation = null;

    protected bool $clusterChanged = false;

    /**
     * Get the fresh discovery evidence for the selected cluster
     *
     * @return NotificationsDiscovery
     */
    public function getDiscovery(): NotificationsDiscovery
    {
        return NotificationsDiscovery::load(Database::connection(), $this->getClusterUuid());
    }

    /**
     * Get the discovery confirmation submitted with the form
     *
     * @return ?string Null for manual setup, or an empty string for a missing confirmation
     */
    public function getDiscoveryConfirmation(): ?string
    {
        return $this->discoveryConfirmation;
    }

    /**
     * Get the listener username the user explicitly selected for HTTP reuse
     *
     * @return ?string
     */
    public function getConfirmedHttpUsername(): ?string
    {
        $username = $this->getPopulatedValue('use_existing_http_source');

        return is_string($username) && str_starts_with($username, 'source:') ? substr($username, 7) : null;
    }

    /**
     * Get the listener username the user explicitly selected for password rotation
     *
     * @return ?string
     */
    public function getHttpPasswordRotationUsername(): ?string
    {
        $username = $this->getPopulatedValue('rotate_http_password');

        return is_string($username) && str_starts_with($username, 'source:') ? substr($username, 7) : null;
    }

    /**
     * Check whether the user is loading settings for another cluster
     *
     * @return bool
     */
    public function isChangingCluster(): bool
    {
        return $this->clusterChanged;
    }

    public static function transformKeyForForm(string $key): string
    {
        return strtr($key, ['notifications.' => 'notifications_']);
    }

    public static function transformKeyForDb(string $key): string
    {
        return strtr($key, ['notifications_' => 'notifications.']);
    }

    public function getClusterUuid(): string
    {
        return $this->getPopulatedValue('cluster_uuid') ??
            (string) Uuid::fromBytes(Cluster::on(Database::connection())->orderBy('uuid')->first()->uuid);
    }

    public function getOldClusterUuid(): string
    {
        return $this->getPopulatedValue('old_cluster_uuid', $this->getClusterUuid());
    }

    /**
     * Get all Notifications settings and their local locks for a cluster
     *
     * @param string $clusterUuid
     *
     * @return array<string, KConfig>
     */
    public function getKConfig(string $clusterUuid): array
    {
        $kconfig = [];
        $q = KConfig::on(Database::connection())
            ->filter(Filter::equal('key', [
                KConfig::NOTIFICATIONS_URL,
                KConfig::NOTIFICATIONS_USERNAME,
                KConfig::NOTIFICATIONS_PASSWORD,
                KConfig::NOTIFICATIONS_ICINGAWEB2_URL
            ]))
            ->filter(Filter::equal('cluster_uuid', $clusterUuid));

        foreach ($q as $r) {
            $kconfig[$r['key']] = $r;
        }

        return $kconfig;
    }

    public function isLocked(): bool
    {
        $this->ensureAssembled();

        return array_reduce(
            array_map(
                fn($element) => $element->getAttributes()->get('disabled')->getValue(),
                array_filter($this->getElements(), fn($element) => ! $element->isIgnored())
            ),
            fn(bool $carry, bool $item) => $carry && $item,
            true
        );
    }

    public function getValue($name, $default = null)
    {
        return parent::getValue(static::transformKeyForForm($name), $default);
    }

    public function getValues(): array
    {
        $values = parent::getValues();

        return array_combine(array_map([static::class, 'transformKeyForDb'], array_keys($values)), $values);
    }

    /**
     * Detect the absolute Url of Icinga Web, respecting X-Forwarded-* headers set by reverse proxies
     *
     * @return string
     */
    protected function getIcingaWeb2Url(): string
    {
        $serverParams = $this->getRequest()->getServerParams();

        $request = new Request(server: $serverParams);
        $request->headers->replace($this->getRequest()->getHeaders());

        // Trusts X-Forwarded-* from any peer: only safe because this form requires config/modules
        Request::setTrustedProxies(
            isset($serverParams['REMOTE_ADDR']) ? [$serverParams['REMOTE_ADDR']] : [],
            Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PREFIX
        );

        return $request->getSchemeAndHttpHost() . $request->getBaseUrl();
    }

    protected function assemble(): void
    {
        $clusterUuid = $this->getClusterUuid();

        if ($clusterUuid !== $this->getOldClusterUuid()) {
            $this->clusterChanged = true;
            $this->clearPopulatedValue('old_cluster_uuid');
            $this->clearPopulatedValue('use_discovered_socket');
            $this->clearPopulatedValue('discovery_confirmation');
            $this->clearPopulatedValue('use_existing_http_source');
            $this->clearPopulatedValue('rotate_http_password');
            $this->clearPopulatedValue(static::transformKeyForForm(KConfig::NOTIFICATIONS_URL));
            $this->clearPopulatedValue(static::transformKeyForForm(KConfig::NOTIFICATIONS_ICINGAWEB2_URL));
        }

        $this->addElement('hidden', 'old_cluster_uuid', ['value' => $clusterUuid, 'ignore' => true]);

        $this->addElement(
            'select',
            'cluster_uuid',
            [
                'required' => true,
                'class'    => 'autosubmit',
                'label'    => $this->translate('Cluster'),
                'options'  => $this->getClusterOptions(),
                'value'    => $clusterUuid,
                'ignore'   => true
            ],
        );

        $kconfig = $this->getKConfig($clusterUuid);

        $discovery = $this->getDiscovery();
        $discoveredUrl = $discovery->socketPath === null
            ? null
            : NotificationsUrl::fromSocketPath($discovery->socketPath);
        $confirmation = $discovery->getConfirmation($clusterUuid);
        $assistanceAvailable = $confirmation !== null
            && ! ($kconfig[KConfig::NOTIFICATIONS_URL]->locked ?? false)
            && (
                ! ($kconfig[KConfig::NOTIFICATIONS_USERNAME]->locked ?? false)
                || ($kconfig[KConfig::NOTIFICATIONS_USERNAME]->value ?? '') === ''
            )
            && (
                ! ($kconfig[KConfig::NOTIFICATIONS_PASSWORD]->locked ?? false)
                || ($kconfig[KConfig::NOTIFICATIONS_PASSWORD]->value ?? '') === ''
            );
        $longUrlMessage = $this->translate(
            'The socket URL is too long to save. Use a shorter path or a symlink accessible to the daemon.'
        );
        if ($assistanceAvailable) {
            $assistanceDescription = sprintf($this->translate(
                'Use "%s" for the process user "%s". A source is created if the username is available.'
            ), $discoveredUrl, $discovery->serviceUser);
        } elseif ($discoveredUrl !== null && strlen($discoveredUrl) > NotificationsUrl::MAX_UNIX_URL_LENGTH) {
            $assistanceDescription = $longUrlMessage;
        } else {
            $assistanceDescription = $this->translate(
                'Assistance requires writable Notifications settings and matching socket paths and process users'
                . ' from live daemon instances.'
            );
        }

        if ($this->getPopulatedValue('use_discovered_socket') === 'y') {
            $submitted = $this->getPopulatedValue('discovery_confirmation');
            $this->discoveryConfirmation = is_string($submitted) ? $submitted : '';
        }

        $previewMatches = $assistanceAvailable && $this->discoveryConfirmation === $confirmation;
        $urlField = static::transformKeyForForm(KConfig::NOTIFICATIONS_URL);
        if (! $previewMatches) {
            $this->clearPopulatedValue('use_discovered_socket');
            if ($this->discoveryConfirmation !== null) {
                $this->clearPopulatedValue($urlField);
            }
        }

        $this->clearPopulatedValue('discovery_confirmation');
        $this->addElement('hidden', 'discovery_confirmation', ['value' => $confirmation, 'ignore' => true]);
        $this->addElement('checkbox', 'use_discovered_socket', [
            'label'       => $this->translate('Use discovered Unix socket'),
            'class'       => 'autosubmit',
            'disabled'    => ! $assistanceAvailable,
            'ignore'      => true,
            'description' => $assistanceDescription,
        ]);

        $this->addElement('text', $urlField, [
            'label'       => $this->translate('Icinga Notifications URL'),
            'disabled'    => $kconfig[KConfig::NOTIFICATIONS_URL]->locked ?? false,
            'readonly'    => $previewMatches,
            'value'       => $kconfig[KConfig::NOTIFICATIONS_URL]->value ?? null,
            'placeholder' => 'http://localhost:5680',
            'description' => $this->translate(
                'Use an HTTP(S) URL or unix:///absolute/path. Leave blank to turn off notifications.'
                . ' Restart Icinga for Kubernetes after saving changes.',
            ),
            'validators'  => [
                'Callback' => function ($value, CallbackValidator $validator) use ($longUrlMessage): bool {
                    if (! NotificationsUrl::isValid($value)) {
                        $validator->addMessage(NotificationsUrl::isUnix($value) ? $longUrlMessage : $this->translate(
                            'Use an HTTP(S) URL with a host or a Unix URL with an absolute socket path.',
                        ));

                        return false;
                    }

                    return true;
                }
            ]
        ]);

        if (
            ! ($kconfig[KConfig::NOTIFICATIONS_URL]->locked ?? false)
            && ($kconfig[KConfig::NOTIFICATIONS_PASSWORD]->value ?? '') !== ''
        ) {
            $httpUser = ($kconfig[KConfig::NOTIFICATIONS_USERNAME]->locked ?? false)
                ? $kconfig[KConfig::NOTIFICATIONS_USERNAME]->value
                : $clusterUuid;
            $this->addElement('checkbox', 'use_existing_http_source', [
                'label'        => $this->translate('Use an existing HTTP source'),
                'ignore'       => true,
                'checkedValue' => 'source:' . $httpUser,
                'description'  => sprintf($this->translate(
                    'Verify that "%s" is the intended Kubernetes source in Icinga Notifications. A source'
                    . ' created by this form is locked and named "%s".'
                ), $httpUser, KConfig::DEFAULT_NOTIFICATIONS_NAME . " ($clusterUuid)"),
            ]);
            if (
                ($kconfig[KConfig::NOTIFICATIONS_PASSWORD]->locked ?? false)
                && ($kconfig[KConfig::NOTIFICATIONS_USERNAME]->value ?? '') === $httpUser
                && NotificationsUrl::isWeb($kconfig[KConfig::NOTIFICATIONS_URL]->value ?? '')
            ) {
                $this->addElement('checkbox', 'rotate_http_password', [
                    'label'        => $this->translate('Apply the locally configured HTTP password'),
                    'ignore'       => true,
                    'checkedValue' => 'source:' . $httpUser,
                    'description'  => sprintf($this->translate(
                        'Verify that "%s" is the listener username of the locked Kubernetes source "%s" before'
                        . ' selecting this option. Coordinate changes to shared sources with their administrators.'
                    ), $httpUser, KConfig::DEFAULT_NOTIFICATIONS_NAME . " ($clusterUuid)"),
                ]);
            }
        }

        $this->addElement('text', static::transformKeyForForm(KConfig::NOTIFICATIONS_ICINGAWEB2_URL), [
            'label'       => $this->translate('Icinga Web URL'),
            'disabled'    => $kconfig[KConfig::NOTIFICATIONS_ICINGAWEB2_URL]->locked ?? false,
            'value'       => $kconfig[KConfig::NOTIFICATIONS_ICINGAWEB2_URL]->value ?? $this->getIcingaWeb2Url(),
            'description' => $this->translate('Icinga Web URL.'),
            'validators'  => [
                'Callback' => function ($value, CallbackValidator $validator): bool {
                    $components = parse_url($value);
                    if ($components === false || ! isset($components['scheme'], $components['host'])) {
                        $validator->addMessage($this->translate(
                            'Invalid URL: Both host and scheme must be specified.'
                        ));

                        return false;
                    }

                    return true;
                }
            ]
        ]);

        $locked = $this->isLocked();

        if ($locked) {
            // TODO(el): Add locked info box.
        }

        $this->addElement('submit', 'submit', [
            'label'    => $this->translate('Save Changes'),
            'disabled' => $locked
        ]);

        if ($previewMatches && ! $this->hasBeenSubmitted()) {
            $this->populate([$urlField => $discoveredUrl]);
        }

        if ($this->discoveryConfirmation !== null && ! $previewMatches && ! $this->hasBeenSubmitted()) {
            $this->prependHtml(new Callout(CalloutType::Warning, $this->translate(
                'The discovered socket or process user changed, or the settings were locked. Confirm them again.'
            )));
        }
    }

    /**
     * Get the available clusters for the selector
     *
     * @return array<string, string>
     */
    protected function getClusterOptions(): array
    {
        return iterator_to_array(ClusterForm::yieldClusters());
    }
}

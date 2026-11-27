<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Test;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Kubernetes\Common\NotificationsDiscovery;
use Icinga\Module\Kubernetes\Forms\NotificationsConfigForm;
use Icinga\Module\Kubernetes\Model\Config;
use PHPUnit\Framework\TestCase;

class NotificationsConfigFormTest extends TestCase
{
    /** @var string */
    protected const CLUSTER = 'd64a8827-2de6-4915-9aad-7c75fb32e798';

    /** @var string */
    protected const OTHER_CLUSTER = '471d54de-dc99-4d5a-b794-a1a17d847980';

    /** @var string */
    protected const SOCKET = '/run/icinga-notifications/api.sock';

    public function testSelectingDiscoveryPrefillsTheConfirmedUrlBeforeSaving(): void
    {
        $discovery = new NotificationsDiscovery(static::SOCKET, 'kubernetes');
        $form = $this->form($discovery);
        $form->handleRequest($this->request($discovery, url: 'https://notifications.example'));

        self::assertFalse($form->hasBeenSubmitted());
        self::assertSame('unix://' . static::SOCKET, $form->getValue(Config::NOTIFICATIONS_URL));
        self::assertSame($discovery->getConfirmation(static::CLUSTER), $form->getDiscoveryConfirmation());
        self::assertTrue($form->getElement('notifications_url')->getAttributes()->get('readonly')->getValue());
    }

    public function testCheckedSavePreservesTheSubmittedIdentityForValidation(): void
    {
        $previous = new NotificationsDiscovery(static::SOCKET, 'previous-user');
        $current = new NotificationsDiscovery('/run/other.sock', 'kubernetes');
        $form = $this->form($current);
        $form->handleRequest($this->request($previous, submit: true));

        self::assertTrue($form->hasBeenSubmitted());
        self::assertNull($form->getValue(Config::NOTIFICATIONS_URL));
        self::assertSame($previous->getConfirmation(static::CLUSTER), $form->getDiscoveryConfirmation());
        self::assertNotSame('y', $form->getElement('use_discovered_socket')->getValue());
        self::assertSame(
            $current->getConfirmation(static::CLUSTER),
            $form->getElement('discovery_confirmation')->getValue(),
        );

        $retryForm = $this->form($current);
        $retry = $this->request($current, submit: true, url: '');
        $retryForm->handleRequest($retry->withParsedBody(array_replace($retry->getParsedBody(), [
            'use_discovered_socket' => 'n',
        ])));

        self::assertNull($retryForm->getDiscoveryConfirmation());
        self::assertSame('', $retryForm->getValue(Config::NOTIFICATIONS_URL, ''));
    }

    public function testCheckedSaveDoesNotReplaceAnEditedUrl(): void
    {
        $discovery = new NotificationsDiscovery(static::SOCKET, 'kubernetes');
        $form = $this->form($discovery);
        $form->handleRequest($this->request($discovery, submit: true, url: 'unix:///run/edited.sock'));

        self::assertSame('unix:///run/edited.sock', $form->getValue(Config::NOTIFICATIONS_URL));
        self::assertSame($discovery->getConfirmation(static::CLUSTER), $form->getDiscoveryConfirmation());
    }

    public function testStaleCheckboxAutosubmitRendersAWarning(): void
    {
        $previous = new NotificationsDiscovery(static::SOCKET, 'previous-user');
        $current = new NotificationsDiscovery('/run/other.sock', 'kubernetes');
        $form = $this->form($current);
        $form->handleRequest($this->request($previous));
        $form->getElement('use_discovered_socket')->getAttributes()->set('id', 'discovery-test');

        $html = $form->render();
        self::assertStringContainsString('callout-type-warning', $html);
        self::assertStringContainsString('The discovered socket or process user changed', $html);
        self::assertNull($form->getValue(Config::NOTIFICATIONS_URL));
    }

    public function testChangingClusterClearsDiscoveryAndSourceConfirmations(): void
    {
        $discovery = new NotificationsDiscovery(static::SOCKET, 'kubernetes');
        $form = $this->form($discovery);
        $request = $this->request($discovery, submit: true);
        $form->handleRequest($request->withParsedBody(array_replace($request->getParsedBody(), [
            'cluster_uuid'             => static::OTHER_CLUSTER,
            'use_existing_http_source' => 'source:' . static::CLUSTER,
        ])));

        self::assertTrue($form->isChangingCluster());
        self::assertNull($form->getDiscoveryConfirmation());
        self::assertNull($form->getConfirmedHttpUsername());
    }

    public function testOverlongSocketUrlDisablesAssistanceAndFailsManualValidation(): void
    {
        $discovery = new NotificationsDiscovery('/' . str_repeat(' ', 82) . 'xx', 'kubernetes');
        $form = $this->form($discovery);
        $request = $this->request($discovery, submit: true, url: 'unix:///' . str_repeat('%20', 82) . 'xx');
        $form->handleRequest($request->withParsedBody(array_replace($request->getParsedBody(), [
            'use_discovered_socket' => 'n',
        ])));

        self::assertTrue($form->getElement('use_discovered_socket')->getAttributes()->get('disabled')->getValue());
        self::assertFalse($form->isValid());
    }

    public function testManualUnixSubmissionIsAvailableWithoutTheSourceApi(): void
    {
        $discovery = new NotificationsDiscovery(static::SOCKET, 'kubernetes');
        $form = $this->form($discovery, sourceApiAvailable: false);
        $request = $this->request($discovery, submit: true);
        $form->handleRequest($request->withParsedBody(array_replace($request->getParsedBody(), [
            'use_discovered_socket' => 'n',
        ])));

        $discoveryControl = $form->getElement('use_discovered_socket');
        self::assertTrue($discoveryControl->getAttributes()->get('disabled')->getValue());
        self::assertStringContainsString('Enable Icinga Notifications Web', $discoveryControl->getDescription());
        self::assertTrue($form->isValid());
        self::assertSame('unix://' . static::SOCKET, $form->getValue(Config::NOTIFICATIONS_URL));
        self::assertNull($form->getDiscoveryConfirmation());
    }

    public function testHttpSubmissionExplainsTheMissingSourceApi(): void
    {
        $discovery = new NotificationsDiscovery(null, null);
        $form = $this->form($discovery, sourceApiAvailable: false);
        $request = $this->request($discovery, submit: true, url: 'https://notifications.example');
        $form->handleRequest($request->withParsedBody(array_replace($request->getParsedBody(), [
            'use_discovered_socket' => 'n',
        ])));

        self::assertFalse($form->isValid());
        self::assertStringContainsString(
            'Enable Icinga Notifications Web',
            implode(' ', $form->getElement('notifications_url')->getMessages()),
        );
    }

    protected function form(
        NotificationsDiscovery $discovery,
        bool $sourceApiAvailable = true,
    ): NotificationsConfigForm {
        $form = $this->createPartialMock(NotificationsConfigForm::class, [
            'getClusterOptions',
            'getDiscovery',
            'getKConfig',
            'hasSourceApi',
        ]);
        $form->expects(self::once())->method('getClusterOptions')->willReturn([
            static::CLUSTER       => 'First Cluster',
            static::OTHER_CLUSTER => 'Second Cluster',
        ]);
        $form->expects(self::once())->method('getDiscovery')->willReturn($discovery);
        $form->expects(self::once())->method('getKConfig')->willReturn([]);
        $form->expects(self::once())->method('hasSourceApi')->willReturn($sourceApiAvailable);

        return $form;
    }

    protected function request(
        NotificationsDiscovery $discovery,
        bool $submit = false,
        string $url = 'unix://' . self::SOCKET,
    ): ServerRequest {
        $body = [
            'cluster_uuid'                 => static::CLUSTER,
            'old_cluster_uuid'             => static::CLUSTER,
            'discovery_confirmation'       => $discovery->getConfirmation(static::CLUSTER),
            'use_discovered_socket'        => 'y',
            'notifications_url'            => $url,
            'notifications_icingaweb2_url' => 'http://web.example',
        ];
        if ($submit) {
            $body['submit'] = 'Save Changes';
        }

        return (new ServerRequest('POST', 'http://web.example/kubernetes/config/notifications'))->withParsedBody($body);
    }
}

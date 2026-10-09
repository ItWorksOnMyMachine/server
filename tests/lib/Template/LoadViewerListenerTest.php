<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Template;

use OC\Template\LoadViewerListener;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Config\IUserConfig;
use OCP\IInitialStateService;
use OCP\IUser;
use OCP\IUserSession;
use Test\TestCase;

class LoadViewerListenerTest extends TestCase {
	/** @var array<string, mixed> */
	private array $provided = [];

	private function listener(?string $userId, int $delay): LoadViewerListener {
		$this->provided = [];
		$initialState = $this->createStub(IInitialStateService::class);
		$initialState->method('provideInitialState')
			->willReturnCallback(function (string $app, string $key, mixed $data): void {
				$this->provided["$app/$key"] = $data;
			});

		$user = null;
		if ($userId !== null) {
			$user = $this->createStub(IUser::class);
			$user->method('getUID')->willReturn($userId);
		}
		$userSession = $this->createStub(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$userConfig = $this->createStub(IUserConfig::class);
		$userConfig->method('getValueInt')->willReturn($delay);

		return new LoadViewerListener($initialState, $userSession, $userConfig);
	}

	private function page(string $renderAs = TemplateResponse::RENDER_AS_USER): BeforeTemplateRenderedEvent {
		return new BeforeTemplateRenderedEvent(true, new TemplateResponse('core', 'empty', [], $renderAs));
	}

	public function testHandsTheViewerTheUsersSettings(): void {
		$this->listener('alice', 10)->handle($this->page());

		$this->assertSame(['slideshowDelay' => 10], $this->provided['viewer/settings']);
	}

	public function testLeavesTheDefaultsToTheViewerWhenNothingIsSet(): void {
		$this->listener('alice', 0)->handle($this->page());

		$this->assertSame(['slideshowDelay' => null], $this->provided['viewer/settings']);
	}

	public function testHasNoSettingsForAGuest(): void {
		$this->listener(null, 10)->handle($this->page());

		$this->assertArrayNotHasKey('viewer/settings', $this->provided);
	}

	public function testLeavesTheErrorPageAlone(): void {
		$this->listener('alice', 10)->handle($this->page(TemplateResponse::RENDER_AS_ERROR));

		$this->assertSame([], $this->provided);
	}
}

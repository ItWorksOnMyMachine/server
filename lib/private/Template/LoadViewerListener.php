<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Template;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IInitialStateService;
use OCP\IUserSession;
use OCP\Util;

/**
 * Put the viewer on every page a file can be opened from, which is any page an
 * app can put a file link on rather than only the Files list. The script only
 * registers the handlers; the viewer itself is fetched when a file is opened.
 *
 * @template-implements IEventListener<BeforeTemplateRenderedEvent>
 */
class LoadViewerListener implements IEventListener {
	public function __construct(
		private IInitialStateService $initialState,
		private IUserSession $userSession,
		private IUserConfig $userConfig,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}

		// The themed error page renders through here too, and nothing on it
		// opens a file
		if ($event->getResponse()->getRenderAs() === TemplateResponse::RENDER_AS_ERROR) {
			return;
		}

		Util::addInitScript('core', 'viewer-init');

		// What the user set in the viewer's settings. A guest has nowhere to
		// keep them, and no settings tells the viewer not to save any.
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}
		$delay = $this->userConfig->getValueInt($user->getUID(), ViewerPreferenceListener::APP_ID, ViewerPreferenceListener::SLIDESHOW_DELAY);
		$this->initialState->provideInitialState(ViewerPreferenceListener::APP_ID, 'settings', [
			'slideshowDelay' => $delay > 0 ? $delay : null,
		]);
	}
}

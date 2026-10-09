<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Template;

use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Let users keep their viewer settings through the provisioning API's
 * preferences, which only stores what a listener allows.
 *
 * @template-implements IEventListener<BeforePreferenceSetEvent|BeforePreferenceDeletedEvent>
 */
class ViewerPreferenceListener implements IEventListener {
	public const APP_ID = 'viewer';
	/** Seconds the slideshow shows each file for */
	public const SLIDESHOW_DELAY = 'slideshow_delay';
	public const SLIDESHOW_DELAY_MIN = 1;
	public const SLIDESHOW_DELAY_MAX = 60;

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof BeforePreferenceDeletedEvent) {
			if ($event->getAppId() === self::APP_ID && $event->getConfigKey() === self::SLIDESHOW_DELAY) {
				$event->setValid(true);
			}
			return;
		}

		if (!($event instanceof BeforePreferenceSetEvent)
			|| $event->getAppId() !== self::APP_ID
			|| $event->getConfigKey() !== self::SLIDESHOW_DELAY) {
			return;
		}

		$delay = filter_var($event->getConfigValue(), FILTER_VALIDATE_INT, [
			'options' => ['min_range' => self::SLIDESHOW_DELAY_MIN, 'max_range' => self::SLIDESHOW_DELAY_MAX],
		]);
		$event->setValid($delay !== false);
	}
}

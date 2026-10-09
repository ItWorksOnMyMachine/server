<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Template;

use OC\Template\ViewerPreferenceListener;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class ViewerPreferenceListenerTest extends TestCase {
	public static function dataSetPreference(): array {
		return [
			'a delay in range' => ['viewer', 'slideshow_delay', '10', true],
			'the shortest' => ['viewer', 'slideshow_delay', '1', true],
			'the longest' => ['viewer', 'slideshow_delay', '60', true],
			'too short' => ['viewer', 'slideshow_delay', '0', false],
			'too long' => ['viewer', 'slideshow_delay', '61', false],
			'not a number' => ['viewer', 'slideshow_delay', 'fast', false],
			'not a whole number' => ['viewer', 'slideshow_delay', '2.5', false],
			'a key it does not know' => ['viewer', 'something_else', '10', false],
			'another app' => ['files', 'slideshow_delay', '10', false],
		];
	}

	#[DataProvider('dataSetPreference')]
	public function testAllowsOnlyTheViewerSettings(string $appId, string $key, string $value, bool $valid): void {
		$event = new BeforePreferenceSetEvent('alice', $appId, $key, $value);
		(new ViewerPreferenceListener())->handle($event);

		$this->assertSame($valid, $event->isValid());
	}

	public function testLetsTheUserGoBackToTheDefault(): void {
		$event = new BeforePreferenceDeletedEvent('alice', 'viewer', 'slideshow_delay');
		(new ViewerPreferenceListener())->handle($event);

		$this->assertTrue($event->isValid());
	}

	public function testLeavesOtherDeletionsAlone(): void {
		$event = new BeforePreferenceDeletedEvent('alice', 'files', 'slideshow_delay');
		(new ViewerPreferenceListener())->handle($event);

		$this->assertFalse($event->isValid());
	}
}

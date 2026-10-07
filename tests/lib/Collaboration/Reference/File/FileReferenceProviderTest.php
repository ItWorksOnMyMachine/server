<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Reference\File;

use OC\Collaboration\Reference\File\FileReferenceProvider;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class FileReferenceProviderTest extends TestCase {
	private const BASE = 'https://cloud.example.com';

	private function provider(IStorage $storage, int $permissions): FileReferenceProvider {
		$urlGenerator = $this->createStub(IURLGenerator::class);
		$urlGenerator->method('getAbsoluteURL')->willReturnCallback(fn (string $url): string => self::BASE . $url);

		$file = $this->createStub(File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getName')->willReturn('report.pdf');
		$file->method('getPath')->willReturn('/alice/files/Documents/report.pdf');
		$file->method('getMimetype')->willReturn('application/pdf');
		$file->method('getPermissions')->willReturn($permissions);
		$file->method('getStorage')->willReturn($storage);

		$userFolder = $this->createStub(IUserFolder::class);
		$userFolder->method('getFirstNodeById')->willReturn($file);
		$userFolder->method('getRelativePath')->willReturn('/Documents/report.pdf');

		$rootFolder = $this->createStub(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($userFolder);

		$user = $this->createStub(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$userSession = $this->createStub(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$l10nFactory = $this->createStub(IFactory::class);
		$l10nFactory->method('get')->willReturn($this->createStub(IL10N::class));

		return new FileReferenceProvider(
			$urlGenerator,
			$rootFolder,
			$userSession,
			$this->createStub(IMimeTypeDetector::class),
			$this->createStub(IPreview::class),
			$l10nFactory,
		);
	}

	private function sharedStorage(bool $canDownload): ISharedStorage {
		$share = $this->createStub(IShare::class);
		$share->method('canDownload')->willReturn($canDownload);
		$storage = $this->createStub(ISharedStorage::class);
		$storage->method('instanceOfStorage')->willReturnCallback(fn (string $class): bool => $class === ISharedStorage::class);
		$storage->method('getShare')->willReturn($share);
		return $storage;
	}

	public static function dataFiles(): array {
		return [
			'own file' => [false, true, Constants::PERMISSION_ALL, 'no'],
			'received share' => [true, true, Constants::PERMISSION_READ, 'no'],
			'received share that forbids downloading' => [true, false, Constants::PERMISSION_READ, 'yes'],
		];
	}

	/**
	 * What the user may do with the file, for a preview to offer only that
	 */
	#[DataProvider('dataFiles')]
	public function testTellsWhatTheUserMayDo(bool $shared, bool $canDownload, int $permissions, string $hideDownload): void {
		$storage = $shared ? $this->sharedStorage($canDownload) : $this->createStub(IStorage::class);
		$reference = $this->provider($storage, $permissions)->resolveReference(self::BASE . '/index.php/f/42');

		$file = $reference?->getRichObject();
		$this->assertSame($permissions, $file['permissions'] ?? null);
		$this->assertSame($hideDownload, $file['hide-download'] ?? null);
	}
}

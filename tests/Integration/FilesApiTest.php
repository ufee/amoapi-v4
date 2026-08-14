<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\File;

/**
 * @group integration
 */
class FilesApiTest extends IntegrationTestCase
{
	/** @var string|null */
	private $uploadedUuid;

	protected function tearDown(): void
	{
		if ($this->uploadedUuid !== null && $this->api !== null) {
			try {
				$this->api->files()->delete($this->uploadedUuid);
			} catch (\Throwable $e) {
				// best-effort
			}
		}
		$this->uploadedUuid = null;
		parent::tearDown();
	}

	public function testUploadFindAndDeleteFile(): void
	{
		try {
			$account = $this->api->account()->get();
			if (empty($account->drive_url)) {
				$this->markTestSkipped('В аккаунте нет drive_url');
			}
			$this->api->setParam('drive_url', $account->drive_url);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Drive API недоступен: ' . $e->getMessage());
		}

		$content = 'amoapi-v4 itest ' . uniqid('', false);
		try {
			$file = $this->api->files()->upload($content, [
				'file_name' => 'amoapi-v4-itest.txt',
				'content_type' => 'text/plain',
			]);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Drive upload недоступен: ' . $e->getMessage());
		}

		$this->assertInstanceOf(File::class, $file);
		$this->assertNotEmpty($file->uuid);
		$this->uploadedUuid = $file->uuid;

		$found = $this->api->files()->find($file->uuid);
		$this->assertInstanceOf(File::class, $found);
		$this->assertSame($file->uuid, $found->uuid);

		$this->assertTrue($this->api->files()->delete($file->uuid));
		$this->uploadedUuid = null;
	}

	public function testListFilesFirstPage(): void
	{
		try {
			$account = $this->api->account()->get();
			if (empty($account->drive_url)) {
				$this->markTestSkipped('В аккаунте нет drive_url');
			}
			$this->api->setParam('drive_url', $account->drive_url);
			$page = $this->api->files()->maxPageRows(5)->paginate()->fetchPage();
			$this->assertIsObject($page);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Drive API недоступен: ' . $e->getMessage());
		}
	}

	public function testStats(): void
	{
		try {
			$account = $this->api->account()->get();
			if (empty($account->drive_url)) {
				$this->markTestSkipped('В аккаунте нет drive_url');
			}
			$this->api->setParam('drive_url', $account->drive_url);
			$stats = $this->api->files()->stats();
		} catch (\Throwable $e) {
			$this->markTestSkipped('Drive stats недоступен: ' . $e->getMessage());
		}

		$this->assertIsObject($stats);
		$this->assertIsInt($stats->limit);
		$this->assertIsInt($stats->used);
		$this->assertGreaterThan(0, $stats->limit);
		$this->assertGreaterThanOrEqual(0, $stats->used);
	}
}

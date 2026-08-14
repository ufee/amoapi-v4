<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Collections\Files;
use Ufee\AmoV4\Models\File;
use Ufee\AmoV4\Models\FileVersion;
use Ufee\AmoV4\Tests\TestCase;

class FilesApiStubTest extends TestCase
{
	private function stubDriveApi()
	{
		$api = $this->makeStubApiClient();
		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');
		return $api;
	}

	public function testGetDriveHostFromAccountCache(): void
	{
		$api = $this->makeStubApiClient();
		$storage = new \Ufee\AmoV4\Api\Cache\AbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);

		$api->pushResponse(200, [
			'id' => 1,
			'name' => 'Acc',
			'drive_url' => 'https://drive-x.amocrm.ru/',
			'_embedded' => (object) [
				'users_groups' => [],
				'task_types' => [],
			],
		]);

		$host = $api->files()->getDriveHost();
		$this->assertSame('drive-x.amocrm.ru', $host);
		$this->assertSame('https://drive-x.amocrm.ru/', $api->getParam('drive_url'));
	}

	public function testCreateSessionUploadFindUpdateDeleteRestore(): void
	{
		$api = $this->stubDriveApi();

		$api->pushResponse(200, [
			'upload_url' => 'https://drive-b.amocrm.ru/upload/part1',
			'max_part_size' => 4,
		]);
		$session = $api->files()->createSession([
			'file_name' => 'a.txt',
			'file_size' => 5,
		]);
		$this->assertSame('https://drive-b.amocrm.ru/upload/part1', $session->upload_url);
		$this->assertSame('drive-b.amocrm.ru', $api->lastQuery->host);

		$api->pushResponse(200, [
			'upload_url' => 'https://drive-b.amocrm.ru/upload/part1',
			'max_part_size' => 3,
		]);
		// 5 байт / part=3 → два чанка
		$api->pushResponse(200, [
			'next_url' => 'https://drive-b.amocrm.ru/upload/part2',
		]);
		$api->pushResponse(200, [
			'uuid' => 'u-1',
			'name' => 'a.txt',
			'size' => 5,
		]);
		$file = $api->files()->upload('hello', ['file_name' => 'a.txt']);
		$this->assertInstanceOf(File::class, $file);
		$this->assertSame('u-1', $file->uuid);

		$api->pushResponse(200, ['uuid' => 'u-1', 'name' => 'a.txt']);
		$found = $api->files()->find('u-1');
		$this->assertSame('u-1', $found->uuid);

		$api->pushResponse(404, '');
		$this->assertNull($api->files()->find('missing'));

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'files' => [
					(object) ['uuid' => 'u-1', 'name' => 'a.txt'],
					(object) ['uuid' => 'u-2', 'name' => 'b.txt'],
				],
			],
		]);
		$api->pushResponse(204, '');
		$many = $api->files()->find(['u-1', 'u-2']);
		$this->assertCount(2, $many);

		$api->pushResponse(200, ['uuid' => 'u-1', 'name' => 'renamed.txt']);
		$updated = $api->files()->update('u-1', ['name' => 'renamed.txt']);
		$this->assertSame('renamed.txt', $updated->name);

		$api->pushResponse(204, '');
		$this->assertTrue($api->files()->delete('u-1'));

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'files' => [
					(object) ['uuid' => 'u-1', 'name' => 'a.txt'],
				],
			],
		]);
		$restored = $api->files()->restore('u-1');
		$this->assertCount(1, $restored);
	}

	public function testUploadFromTempFile(): void
	{
		$api = $this->stubDriveApi();
		$path = sys_get_temp_dir() . '/amoapi-file-' . uniqid('', true) . '.txt';
		file_put_contents($path, 'abcdef');

		try {
			$api->pushResponse(200, [
				'upload_url' => 'https://drive-b.amocrm.ru/upload/x',
				'max_part_size' => 100,
			]);
			$api->pushResponse(200, [
				'uuid' => 'file-path',
				'name' => basename($path),
			]);
			$file = $api->files()->upload($path);
			$this->assertSame('file-path', $file->uuid);
		} finally {
			@unlink($path);
		}
	}

	public function testSearchVersionsAndLinks(): void
	{
		$api = $this->stubDriveApi();

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'files' => [(object) ['uuid' => 'u-1', 'name' => 'doc']],
			],
		]);
		$api->pushResponse(204, '');
		$page = $api->files()->search('doc')->fetchAll();
		$this->assertCount(1, $page);
		$this->assertSame(['term' => 'doc'], $api->lastQuery->args['filter']);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'versions' => [
					(object) ['uuid' => 'v-1', 'name' => 'v1'],
				],
			],
		]);
		$versions = $api->files()->versions('u-1');
		$this->assertInstanceOf(FileVersion::class, $versions->first());

		$api->pushResponse(204, '');
		$this->assertCount(0, $api->files()->versions('empty'));

		$api->pushResponse(200, ['_embedded' => (object) ['leads' => []]]);
		$links = $api->files()->getLinks('u-1');
		$this->assertIsObject($links);
		$this->assertStringContainsString('/api/v4/files/u-1/links', $api->lastQuery->url);
	}

	public function testStats(): void
	{
		$api = $this->stubDriveApi();
		$api->pushResponse(200, [
			'limit' => 1048576000,
			'used' => 13320204,
			'_links' => (object) [
				'self' => (object) ['href' => 'https://drive-b.amocrm.ru/v1.0/files/stats'],
			],
		]);

		$stats = $api->files()->stats();
		$this->assertSame(1048576000, $stats->limit);
		$this->assertSame(13320204, $stats->used);
		$this->assertSame('GET', $api->lastQuery->method);
		$this->assertSame('/v1.0/files/stats', $api->lastQuery->url);
		$this->assertSame('drive-b.amocrm.ru', $api->lastQuery->host);
	}

	public function testStatsRejectsInvalidResponse(): void
	{
		$api = $this->stubDriveApi();
		$api->pushResponse(200, ['_links' => (object) []]);

		$this->expectException(\UnexpectedValueException::class);
		$this->expectExceptionMessage('limit or used is missing');
		$api->files()->stats();
	}

	public function testFileModelSaveDeleteRestoreHelpers(): void
	{
		$api = $this->stubDriveApi();
		$file = $api->files()->create([
			'uuid' => 'u-9',
			'name' => 'old',
			'_links' => [
				'download' => (object) ['href' => 'https://d/1'],
				'download_version' => ['href' => 'https://d/v'],
			],
		]);

		$this->assertSame('https://d/1', $file->getDownloadUrl());
		$this->assertSame('https://d/v', $file->getDownloadVersionUrl());

		$api->pushResponse(200, [
			'uuid' => 'u-9',
			'name' => 'new',
			'_links' => (object) ['download' => (object) ['href' => 'https://d/2']],
		]);
		$file->name = 'new';
		$this->assertTrue($file->save());
		$this->assertSame('new', $file->name);
		$this->assertSame('https://d/2', $file->getDownloadUrl());

		$api->pushResponse(204, '');
		$this->assertTrue($file->delete());

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'files' => [
					(object) [
						'uuid' => 'u-9',
						'name' => 'restored',
						'_links' => (object) ['download' => (object) ['href' => 'https://d/3']],
					],
				],
			],
		]);
		$this->assertTrue($file->restore());
		$this->assertSame('restored', $file->name);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'versions' => [(object) ['uuid' => 'v-1']],
			],
		]);
		$this->assertCount(1, $file->versions());

		$api->pushResponse(200, ['ok' => true]);
		$this->assertTrue($file->getEntityLinks()->ok);
	}

	public function testFilesCollectionDeleteRestoreAndSaveGuard(): void
	{
		$api = $this->stubDriveApi();
		$collection = $api->files()->createCollection([
			['uuid' => 'a', 'name' => 'A'],
			['uuid' => 'b', 'name' => 'B'],
		]);

		$api->pushResponse(204, '');
		$this->assertTrue($collection->delete());

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'files' => [
					(object) ['uuid' => 'a', 'name' => 'A'],
					(object) ['uuid' => 'b', 'name' => 'B'],
				],
			],
		]);
		$restored = $collection->restore();
		$this->assertInstanceOf(Files::class, $restored);

		$empty = new Files([]);
		$this->assertFalse($empty->delete());
		$this->assertFalse($empty->restore());

		$this->expectException(\BadMethodCallException::class);
		$collection->save();
	}

	public function testUpdateValidation(): void
	{
		$api = $this->stubDriveApi();
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('cannot be set simultaneously');
		$api->files()->update('u-1', ['name' => 'a', 'version_uuid' => 'v-1']);
	}
}

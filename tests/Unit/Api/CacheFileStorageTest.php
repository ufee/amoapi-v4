<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Api\Cache\FileStorage;
use Ufee\AmoV4\Tests\TestCase;

class CacheFileStorageTest extends TestCase
{
	/** @var string|null */
	private $tempDir;

	protected function tearDown(): void
	{
		if ($this->tempDir && is_dir($this->tempDir)) {
			foreach (glob($this->tempDir . '/*/*') ?: [] as $file) {
				@unlink($file);
			}
			foreach (glob($this->tempDir . '/*') ?: [] as $dir) {
				@rmdir($dir);
			}
			@rmdir($this->tempDir);
		}
		parent::tearDown();
	}

	public function testSetGetHasAndClear(): void
	{
		$api = $this->makeApiClient(['domain' => 'cache-test']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-cache-' . uniqid('', true);
		$storage = new FileStorage($api, ['path' => $this->tempDir]);
		$storage->initialize();

		$payload = (object) ['id' => 1, 'name' => 'Cached'];
		$this->assertTrue((bool) $storage->set('account', $payload, 60));
		$this->assertTrue($storage->has('account'));
		$this->assertSame(1, $storage->get('account')->id);

		$path = $this->tempDir . '/cache-test/' . $api->client_id . '-account.cache';
		$this->assertFileExists($path);

		$storage->clear('account');
		$this->assertNull($storage->get('account'));
		$this->assertFileDoesNotExist($path);
	}

	public function testExpiredFileIsRemoved(): void
	{
		$api = $this->makeApiClient(['domain' => 'cache-expire']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-cache-exp-' . uniqid('', true);
		$storage = new FileStorage($api, ['path' => $this->tempDir]);
		$storage->initialize();

		$key = 'pipelines';
		$path = $this->tempDir . '/cache-expire/' . $api->client_id . '-' . $key . '.cache';
		file_put_contents($path, serialize([
			'expire_at' => time() - 1,
			'payload' => (object) ['stale' => true],
		]));

		// сброс локального кеша AbstractStorage
		$storage->clear();
		$this->assertNull($storage->get($key));
		$this->assertFileDoesNotExist($path);
	}

	public function testRequiresPathOption(): void
	{
		$api = $this->makeApiClient();
		$this->expectException(\InvalidArgumentException::class);
		new FileStorage($api, []);
	}

	public function testClearAllFiles(): void
	{
		$api = $this->makeApiClient(['domain' => 'cache-all']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-cache-all-' . uniqid('', true);
		$storage = new FileStorage($api, ['path' => $this->tempDir]);
		$storage->initialize();

		$storage->set('a', (object) ['v' => 1], 60);
		$storage->set('b', (object) ['v' => 2], 60);
		$storage->clear();

		$this->assertNull($storage->get('a'));
		$this->assertNull($storage->get('b'));
		$this->assertSame([], glob($this->tempDir . '/cache-all/' . $api->client_id . '-*.cache') ?: []);
	}
}

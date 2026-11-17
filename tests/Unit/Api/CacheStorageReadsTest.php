<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Api\Cache\AbstractStorage;
use Ufee\AmoV4\Api\Cache\FileStorage;
use Ufee\AmoV4\Api\Cache\RedisStorage;
use Ufee\AmoV4\Tests\Support\CountingRedis;
use Ufee\AmoV4\Tests\TestCase;

/**
 * Хранилище читается один раз на ключ за процесс, дальше — локальный кеш
 */
class CacheStorageReadsTest extends TestCase
{
	/** @var string|null */
	private $dir;

	protected function tearDown(): void
	{
		if ($this->dir) {
			foreach (glob($this->dir . '/*/*') ?: [] as $file) {
				@unlink($file);
			}
			foreach (glob($this->dir . '/*') ?: [] as $sub) {
				@rmdir($sub);
			}
			@rmdir($this->dir);
		}
		parent::tearDown();
	}

	private function redisKey($api, string $key): string
	{
		return $api->getIntegration('domain') . ':' . $api->client_id . ':' . RedisStorage::FORMAT . ':' . $key;
	}

	public function testRedisReadOncePerProcess(): void
	{
		$api = $this->makeApiClient(['domain' => 'cache-reads']);
		$redis = new CountingRedis();
		(new RedisStorage($api, ['connection' => $redis]))->set('pipelines', (object) ['id' => 5], 60);

		// новый процесс: локальный кеш пуст, запись в Redis
		$storage = new RedisStorage($api, ['connection' => $redis]);
		$first = $storage->get('pipelines');
		for ($i = 0; $i < 50; $i++) {
			$this->assertSame($first, $storage->get('pipelines'));
		}
		$this->assertSame(5, $first->id);
		$this->assertSame(1, $redis->calls['get']);
		$this->assertTrue($storage->has('pipelines'));
		$this->assertSame(0, $redis->calls['exists']);
	}

	public function testCacheFacadeHitsRedisOnce(): void
	{
		$redis = new CountingRedis();
		$api = $this->makeStubApiClient();
		$api->cache->setStorageRedis($redis);
		$api->pushResponse(200, ['_embedded' => ['pipelines' => [['id' => 5, 'name' => 'P']]]]);
		$api->pushResponse(204, []);
		$api->cache->pipelines();

		// новое хранилище — как новый процесс с прогретым Redis
		$api->cache->setStorageRedis($redis);
		$redis->calls['get'] = 0;
		for ($i = 0; $i < 250; $i++) {
			$this->assertSame(5, $api->cache->pipeline(5)->id);
		}
		$this->assertSame(1, $redis->calls['get']);
		$this->assertSame($api->cache->pipeline(5), $api->cache->pipeline(5));
	}

	public function testRedisSkipsExpiredAndLegacyRecords(): void
	{
		$api = $this->makeApiClient(['domain' => 'cache-legacy']);
		$redis = new CountingRedis();
		$redis->setEx($this->redisKey($api, 'old'), 60, (object) ['v2' => 'payload']);
		$redis->setEx($this->redisKey($api, 'expired'), 60, ['expire_at' => time() - 1, 'payload' => (object) []]);

		$storage = new RedisStorage($api, ['connection' => $redis]);
		$this->assertNull($storage->get('old'));
		$this->assertNull($storage->get('expired'));
		$this->assertNull($storage->get('missing'));
		$this->assertFalse($storage->has('missing'));
	}

	public function testRedisClearScansWithoutKeys(): void
	{
		$api = $this->makeApiClient(['domain' => 'cache-clear']);
		$redis = new CountingRedis();
		$storage = new RedisStorage($api, ['connection' => $redis]);
		foreach (['a', 'b', 'c'] as $key) {
			$storage->set($key, (object) ['k' => $key], 60);
		}
		$redis->data['other:client:v3:a'] = serialize('keep');

		$this->assertTrue($storage->clear());
		$this->assertSame(['other:client:v3:a'], array_keys($redis->data));
		$this->assertSame(0, $redis->calls['keys']);
		$this->assertSame(3, $redis->calls['scan']);
		$this->assertNull($storage->get('a'));
	}

	public function testFileReadOncePerProcessAndBrokenFile(): void
	{
		$this->dir = sys_get_temp_dir() . '/amoapi-cache-reads-' . uniqid('', true);
		$api = $this->makeApiClient(['domain' => 'cache-files']);
		(new FileStorage($api, ['path' => $this->dir]))->set('users', (object) ['id' => 7], 60);

		$storage = new FileStorage($api, ['path' => $this->dir]);
		$first = $storage->get('users');
		$path = $this->dir . '/cache-files/' . $api->client_id . '-' . FileStorage::FORMAT . '-users.cache';
		unlink($path);
		// файла уже нет: повторное чтение из памяти
		$this->assertSame($first, $storage->get('users'));

		file_put_contents($path, 'broken');
		$fresh = new FileStorage($api, ['path' => $this->dir]);
		$this->assertNull($fresh->get('users'));
		$this->assertFileExists($path);

		file_put_contents($path, '');
		$this->assertNull($fresh->get('users'));

		// просроченная запись удаляется
		file_put_contents($path, serialize(['expire_at' => time() - 1, 'payload' => (object) []]));
		$this->assertNull($fresh->get('users'));
		$this->assertFileDoesNotExist($path);
	}

	public function testHasRespectsExpiry(): void
	{
		$api = $this->makeApiClient(['domain' => 'cache-has']);
		$storage = new AbstractStorage($api, []);
		$storage->set('k', (object) [], -1);
		$this->assertFalse($storage->has('k'));
		$storage->set('k', (object) [], 60);
		$this->assertTrue($storage->has('k'));
	}
}

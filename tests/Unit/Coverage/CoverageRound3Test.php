<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Coverage;

use Ufee\AmoV4\Api\Cache\AbstractStorage as CacheAbstractStorage;
use Ufee\AmoV4\Api\Cache\RedisStorage as CacheRedisStorage;
use Ufee\AmoV4\Api\Oauth\FileStorage;
use Ufee\AmoV4\Api\Oauth\MongoDbStorage;
use Ufee\AmoV4\Api\Oauth\RedisStorage as OauthRedisStorage;
use Ufee\AmoV4\Api\Paginate;
use Ufee\AmoV4\Collections\CustomFields;
use Ufee\AmoV4\Collections\Links;
use Ufee\AmoV4\Exceptions\AmoException;
use Ufee\AmoV4\Exceptions\OauthException;
use Ufee\AmoV4\Models\AccountCfield;
use Ufee\AmoV4\Models\FileVersion;
use Ufee\AmoV4\Tests\Support\StubQuery;
use Ufee\AmoV4\Tests\TestCase;

class CoverageRound3Test extends TestCase
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

	private function seedCache($api): CacheAbstractStorage
	{
		$storage = new CacheAbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);
		return $storage;
	}

	public function testOauthFetchTokenSuccessAndErrors(): void
	{
		$api = $this->makeStubApiClient(['domain' => 'oauth-fetch']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-oauth-fetch-' . uniqid('', true);
		mkdir($this->tempDir . '/oauth-fetch', 0777, true);
		$api->oauth->setStorage(new FileStorage($api, ['path' => $this->tempDir]));

		$fetched = false;
		$api->callbacks->on('oauth.token.fetch', function () use (&$fetched) {
			$fetched = true;
		});
		$api->pushResponse(200, [
			'token_type' => 'Bearer',
			'expires_in' => 3600,
			'access_token' => 'by-code',
			'refresh_token' => 'r-code',
		]);
		$result = $api->oauth->fetchToken('auth-code');
		$this->assertTrue($fetched);
		$this->assertSame('by-code', $result['access_token']);
		$this->assertSame('by-code', $api->oauth->get('access_token'));

		$api->pushResponse(400, ['hint' => 'bad code']);
		try {
			$api->oauth->fetchToken('bad');
			$this->fail('expected OauthException');
		} catch (OauthException $e) {
			$this->assertStringContainsString('bad code', $e->getMessage());
		}

		$api->pushResponse(400, [
			'status' => 400,
			'title' => 'Bad Request',
			'detail' => 'invalid_grant',
		]);
		try {
			$api->oauth->fetchToken('bad2');
			$this->fail('expected OauthException');
		} catch (OauthException $e) {
			$this->assertStringContainsString('invalid_grant', $e->getMessage());
		}

		$api->pushResponse(500, 'not-json');
		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('non JSON');
		$api->oauth->fetchToken('x');
	}

	public function testOauthRefreshLockUnlockAndStatusError(): void
	{
		$api = $this->makeStubApiClient(['domain' => 'oauth-lock']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-oauth-lock-' . uniqid('', true);
		mkdir($this->tempDir . '/oauth-lock', 0777, true);
		$api->oauth->setStorage(new FileStorage($api, ['path' => $this->tempDir]));
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 3600,
			'access_token' => 'a',
			'refresh_token' => 'r',
			'created_at' => time(),
		]);

		$locked = $unlocked = false;
		$api->callbacks->on('oauth.token.refresh.lock', function () use (&$locked) {
			$locked = true;
			return true;
		});
		$api->callbacks->on('oauth.token.refresh.unlock', function () use (&$unlocked) {
			$unlocked = true;
		});
		$api->pushResponse(200, [
			'token_type' => 'Bearer',
			'expires_in' => 7200,
			'access_token' => 'locked-ok',
			'refresh_token' => 'r2',
		]);
		$result = $api->oauth->refreshToken();
		$this->assertTrue($locked);
		$this->assertTrue($unlocked);
		$this->assertSame('locked-ok', $result['access_token']);

		$api->pushResponse(400, [
			'status' => 400,
			'title' => 'Error',
			'detail' => 'refresh failed',
		]);
		$this->expectException(OauthException::class);
		$this->expectExceptionMessage('refresh failed');
		$api->oauth->refreshToken();
	}

	public function testOauthRefreshUsesParallelTokenUnderLock(): void
	{
		$api = $this->makeStubApiClient(['domain' => 'oauth-parallel']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-oauth-parallel-' . uniqid('', true);
		mkdir($this->tempDir . '/oauth-parallel', 0777, true);
		$storage = new FileStorage($api, ['path' => $this->tempDir]);
		$api->oauth->setStorage($storage);
		$storage->set([
			'token_type' => 'Bearer',
			'expires_in' => 86400,
			'access_token' => 'from-other-process',
			'refresh_token' => 'r-other',
			'created_at' => time(),
		]);

		$api->callbacks->on('oauth.token.refresh.lock', function () {
			return false;
		});

		$result = $api->oauth->refreshToken('unused');
		$this->assertSame('from-other-process', $result['access_token']);
	}

	public function testOauthSetStorageHelpersAndInvalidGet(): void
	{
		$api = $this->makeApiClient(['domain' => 'oauth-helpers']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-oauth-helpers-' . uniqid('', true);
		mkdir($this->tempDir . '/oauth-helpers', 0777, true);

		$api->oauth->setStorageFiles($this->tempDir);
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 100,
			'access_token' => 'f',
			'refresh_token' => 'r',
			'created_at' => time(),
		]);
		$this->assertSame('f', $api->oauth->get('access_token'));

		$redis = new \Redis();
		$api->oauth->setStorageRedis($redis);
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 100,
			'access_token' => 'redis-tok',
			'refresh_token' => 'rr',
			'created_at' => time(),
		]);
		$this->assertSame('redis-tok', $api->oauth->get('access_token'));

		$mongo = new \MongoDB\Collection();
		$api->oauth->setStorageMongo($mongo);
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 100,
			'access_token' => 'mongo-tok',
			'refresh_token' => 'mr',
			'created_at' => time(),
		]);
		$this->assertSame('mongo-tok', $api->oauth->get('access_token'));

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Invalid Oauth field');
		$api->oauth->nope;
	}

	public function testOauthRedisStorageValidationAndRoundtrip(): void
	{
		$api = $this->makeApiClient(['domain' => 'redis-oauth']);

		try {
			new OauthRedisStorage($api, []);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('Redis', $e->getMessage());
		}

		$redis = new \Redis();
		$storage = new OauthRedisStorage($api, ['connection' => $redis]);
		$oauth = [
			'token_type' => 'Bearer',
			'expires_in' => 60,
			'access_token' => 'ra',
			'refresh_token' => 'rr',
			'created_at' => time(),
		];
		$this->assertTrue($storage->set($oauth));
		$this->assertSame('ra', $storage->getRaw()['access_token']);

		$emptyApi = $this->makeApiClient(['domain' => 'redis-oauth-empty']);
		$this->assertFalse((new OauthRedisStorage($emptyApi, ['connection' => new \Redis()]))->getRaw());
	}

	public function testCacheRedisStorageRoundtrip(): void
	{
		$api = $this->makeApiClient(['domain' => 'cache-redis']);

		try {
			new CacheRedisStorage($api, []);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('Redis', $e->getMessage());
		}

		$redis = new \Redis();
		$storage = new CacheRedisStorage($api, ['connection' => $redis]);
		$storage->initialize();

		$payload = (object) ['ok' => 1];
		$this->assertTrue($storage->set('k1', $payload, 30));
		$this->assertTrue($storage->has('k1'));
		$this->assertSame(1, $storage->get('k1')->ok);

		// только Redis, без локального кеша
		$redisKey = $api->getIntegration('domain') . ':' . $api->client_id . ':k2';
		$redis->setEx($redisKey, 30, (object) ['ok' => 2]);
		$fromRedis = new CacheRedisStorage($api, ['connection' => $redis]);
		$this->assertTrue((bool) $fromRedis->has('k2'));
		$this->assertSame(2, $fromRedis->get('k2')->ok);

		$storage->clear('k1');
		$this->assertFalse((bool) $storage->has('k1'));

		$storage->set('a', (object) ['x' => 1], 30);
		$storage->set('b', (object) ['x' => 2], 30);
		$storage->clear();
		$this->assertSame([], $redis->keys($api->getIntegration('domain') . ':' . $api->client_id . ':*'));

		$api->cache->setStorageRedis($redis);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-cache-files-' . uniqid('', true);
		mkdir($this->tempDir, 0777, true);
		$api->cache->setStorageFiles($this->tempDir);
		$this->assertNotNull($api->cache->instance);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Invalid Cache field');
		$api->cache->nope;
	}

	public function testMongoDbStorageRoundtripAndValidation(): void
	{
		$api = $this->makeApiClient(['domain' => 'mongo-store']);
		try {
			new MongoDbStorage($api, []);
			$this->fail('expected Exception');
		} catch (\Exception $e) {
			$this->assertStringContainsString('MongoDB', $e->getMessage());
		}

		$collection = new \MongoDB\Collection();
		$storage = new MongoDbStorage($api, ['collection' => $collection]);
		$this->assertFalse($storage->getRaw());
		$oauth = [
			'token_type' => 'Bearer',
			'expires_in' => 60,
			'access_token' => 'm',
			'refresh_token' => 'mr',
			'created_at' => time(),
		];
		$this->assertTrue($storage->set($oauth));
		$raw = $storage->getRaw();
		$this->assertSame('m', $raw['access_token']);
	}

	public function testPaginateIteratorAndLimits(): void
	{
		$api = $this->makeStubApiClient();
		$query = new StubQuery($api);
		$query->setMethod('GET')->setUrl('/api/v4/contacts');
		$query->pushResponse(200, [
			'_page' => 1,
			'_total_items' => 1,
			'_links' => (object) [
				'next' => (object) ['href' => 'https://example.amocrm.ru/api/v4/contacts?page=2'],
			],
			'_embedded' => (object) [
				'contacts' => [(object) ['id' => 1, 'name' => 'A']],
			],
		]);
		$query->pushResponse(200, [
			'_page' => 2,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [(object) ['id' => 2, 'name' => 'B']],
			],
		]);

		$paginate = new Paginate($query, $api->contacts());
		$this->assertSame($query, $paginate->query);
		$this->assertSame(1, $paginate->page);

		$this->assertTrue($paginate->hasNext());
		$this->assertTrue($paginate->nextPage());
		$this->assertSame(2, $paginate->pageNum());
		$this->assertFalse($paginate->hasNext());
		$this->assertFalse($paginate->nextPage());

		$paginate->next();
		$this->assertSame(2, $paginate->pageNum());

		$query2 = new StubQuery($api);
		$query2->pushResponse(200, [
			'_page' => 1,
			'_total_items' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [(object) ['id' => 1, 'name' => 'A']],
			],
		]);
		$p2 = new Paginate($query2, $api->contacts());
		$p2->maxPages(1);
		foreach ($p2 as $pageNum => $models) {
			$this->assertSame(1, $pageNum);
			$this->assertCount(1, $models);
		}
		$this->assertFalse($p2->valid());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Invalid Paginate field');
		$paginate->foo;
	}

	public function testEntityCustomFieldsLookupFromAccountCache(): void
	{
		$api = $this->makeApiClient();
		$storage = $this->seedCache($api);
		$cf = new AccountCfield([
			'id' => 55,
			'name' => 'City',
			'code' => 'CITY',
			'type' => 'text',
		], $api->customFields('contacts'));
		$storage->set('customFields-contacts', new CustomFields([$cf]), 60);

		$model = $api->contacts()->create(['id' => 1, 'name' => 'C']);
		$fields = $model->cf();
		$byName = $fields->byName('City');
		$this->assertSame(55, $byName->field_id);
		$this->assertSame(55, $fields->byCode('CITY')->field_id);
		$this->assertGreaterThan(0, $fields->all()->count());
		$this->assertSame(55, $fields->byId(55)->field_id);
	}

	public function testNotePinnedHelpersAndTagsEmpty(): void
	{
		$api = $this->makeStubApiClient();
		$note = $api->leads()->notes()->create([
			'id' => 1,
			'note_type' => 'common',
			'params' => ['text' => 'x'],
		]);
		$this->assertNull($note->isPinned());

		$note = $api->leads()->notes()->create([
			'id' => 2,
			'note_type' => 'common',
			'params' => ['text' => 'y'],
			'is_pinned' => true,
		]);
		$this->assertTrue($note->isPinned());
		$api->pushResponse(204, '');
		$this->assertTrue($note->unpin());
		$this->assertFalse($note->is_pinned);

		$contact = $api->contacts()->create(['id' => 1, 'name' => 'T']);
		$this->assertSame($contact, $contact->setTags([]));
		$contact->detachTag(7);
		$payload = $contact->getChangedRawData();
		$this->assertNotEmpty($payload->tags_to_delete);
	}

	public function testLinkedLeadsViaLinks(): void
	{
		$api = $this->makeStubApiClient();
		$contact = $api->contacts()->create(['id' => 10, 'name' => 'C']);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 10,
						'to_entity_id' => 99,
						'to_entity_type' => 'leads',
					],
				],
			],
		]);
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'leads' => [(object) ['id' => 99, 'name' => 'L']],
			],
		]);
		$api->pushResponse(204, '');
		$leads = $contact->leads();
		$this->assertNotFalse($leads);
		$this->assertSame(99, $leads->first()->id);
	}

	public function testLinksGetWithoutEmbeddedThrows(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, ['id' => 1]);
		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('embedded not found');
		$api->links('contacts', 1)->get();
	}

	public function testLinksCollectionEdgeCases(): void
	{
		$api = $this->makeStubApiClient();
		$service = $api->links('leads', 1);
		$leadsOnly = $service->createCollection([
			['entity_id' => 1, 'to_entity_id' => 2, 'to_entity_type' => 'leads'],
		]);
		$this->assertFalse($leadsOnly->contacts());
		$this->assertFalse($leadsOnly->company());

		$catalogLinks = $service->createCollection([
			[
				'entity_id' => 1,
				'to_entity_id' => 9,
				'to_entity_type' => 'catalog_elements',
				'metadata' => (object) [],
			],
		]);
		$this->assertFalse($catalogLinks->catalogElements());

		$api->pushResponse(400, '');
		$this->assertFalse($leadsOnly->delete());
	}

	public function testFileVersionHrefFallbackAndAccountGetDefaultWith(): void
	{
		$api = $this->makeStubApiClient();
		$version = new FileVersion([
			'uuid' => 'v1',
			'_links' => ['download' => 'plain-string'],
		], $api->files());
		$this->assertNull($version->getDownloadUrl());

		$api->pushResponse(200, [
			'id' => 7,
			'name' => 'Acc',
			'_embedded' => (object) [
				'users_groups' => [],
				'task_types' => [],
			],
		]);
		$account = $api->account()->get();
		$this->assertSame(7, $account->id);
	}

	public function testCacheAbstractStorageLazyInitAndExpire(): void
	{
		$api = $this->makeApiClient(['domain' => 'abs-cache']);
		$storage = new CacheAbstractStorage($api, []);
		$this->assertFalse($storage->has('x'));
		$this->assertNull($storage->get('x'));
		$this->assertTrue($storage->set('x', (object) ['v' => 1], -1));
		$this->assertNull($storage->get('x'));
	}

	public function testBotsValidationErrors(): void
	{
		$api = $this->makeStubApiClient();
		$bots = $api->bots();

		try {
			$bots->run('bad');
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('expects tasks', $e->getMessage());
		}

		try {
			$bots->run([]);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('can not be empty', $e->getMessage());
		}

		try {
			$bots->run([['bot_id' => 0, 'entity_id' => 1, 'entity_type' => 'leads']]);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('bot_id', $e->getMessage());
		}

		try {
			$bots->run([['bot_id' => 1, 'entity_id' => 0, 'entity_type' => 'leads']]);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('entity_id', $e->getMessage());
		}

		try {
			$bots->stop(0, 1);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('Bot ID', $e->getMessage());
		}

		$this->expectException(\InvalidArgumentException::class);
		$bots->stop(1, 0);
	}

	public function testFilesTraitInvalidUuid(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create(['id' => 1, 'name' => 'L']);

		try {
			$lead->attachFiles(123);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('File UUIDs', $e->getMessage());
		}

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid file UUID');
		$lead->attachFiles([1]);
	}

	public function testCollectionHelpersCoverage(): void
	{
		$c = new Links([
			['id' => 2, 'group' => 'a'],
			['id' => 1, 'group' => 'b'],
		]);
		$c->uasort(function ($a, $b) {
			return $a['id'] <=> $b['id'];
		});
		$this->assertSame(1, $c->first()['id']);
		$c->sortBy('id', 'DESC');
		$this->assertSame(2, $c->first()['id']);
		$this->assertEquals(3, $c->sum('id'));
		$this->assertFalse($c->contains('id', 99));
		$this->assertSame($c->last(), $c->end());

		$objs = new Links([
			(object) ['id' => 1, 'name' => 'a'],
			(object) ['id' => 2],
		]);
		$this->assertCount(1, $objs->find('name', 'a'));
		$this->assertSame(6, (new Links([1, 2, 3]))->sum());

		$scalars = new Links(['x', 'y']);
		$scalars->sortBy('noop');
		$this->assertCount(2, $scalars);

		try {
			$c->map('not-callable');
			$this->fail('expected Exception');
		} catch (\Exception $e) {
			$this->assertStringContainsString('callback', $e->getMessage());
		}
	}
}

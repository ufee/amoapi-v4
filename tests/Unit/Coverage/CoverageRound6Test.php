<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Coverage;

use Ufee\AmoV4\Api\Oauth\FileStorage as OauthFileStorage;
use Ufee\AmoV4\Api\Paginate;
use Ufee\AmoV4\Api\Query;
use Ufee\AmoV4\Collections\Entities;
use Ufee\AmoV4\Collections\Links;
use Ufee\AmoV4\Exceptions\OauthException;
use Ufee\AmoV4\Tests\Support\LocalHttpServer;
use Ufee\AmoV4\Tests\Support\StubQuery;
use Ufee\AmoV4\Tests\TestCase;

class CoverageRound6Test extends TestCase
{
	/** @var string|null */
	private $tempDir;

	/** @var LocalHttpServer|null */
	private $server;

	protected function tearDown(): void
	{
		if ($this->server) {
			$this->server->stop();
			$this->server = null;
		}
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

	public function testRealQueryExecuteRefreshesTokenAndDestruct(): void
	{
		$this->server = new LocalHttpServer();
		$api = $this->makeStubApiClient(['domain' => 'real-exec']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-real-exec-' . uniqid('', true);
		mkdir($this->tempDir . '/real-exec', 0777, true);
		$api->oauth->setStorage(new OauthFileStorage($api, ['path' => $this->tempDir]));
		$api->setParam('token_refresh_time', 900);
		$api->setParam('query_delay', 0);
		$api->callbacks->off('query.response.code');
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 100,
			'access_token' => 'old',
			'refresh_token' => 'r',
			'created_at' => time() - 50,
		]);

		// refresh через StubQuery::post, сам HTTP — реальный Query::execute
		$api->pushResponse(200, [
			'token_type' => 'Bearer',
			'expires_in' => 86400,
			'access_token' => 'fresh',
			'refresh_token' => 'r2',
		]);

		$query = new Query($api);
		$query->setMethod('GET')->setUrl($this->server->url('/api/v4/contacts/1'));
		$query->prepare();
		$this->assertTrue($query->execute());
		$this->assertSame('fresh', $api->oauth->get('access_token'));

		// __destruct закрывает curl
		$query2 = new Query($api);
		$query2->prepare();
		$this->assertNotNull($query2->curl);
		unset($query2);
	}

	public function testOauthLockTimeoutAndStorageGetter(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('oauth_lock_usleep', 0);
		$api->setParam('oauth_lock_retries', 2);
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 10,
			'access_token' => 'a',
			'refresh_token' => 'r',
			'created_at' => time() - 100, // не «свежий» для parallel shortcut
		]);
		$api->callbacks->on('oauth.token.refresh.lock', function () {
			return false;
		});

		$this->expectException(OauthException::class);
		$this->expectExceptionMessage('OAuth refresh lock timeout');
		$api->oauth->refreshToken();
	}

	public function testOauthAndCachePropertyGetters(): void
	{
		$api = $this->makeApiClient();
		$api->oauth->setLongToken('tok');
		$this->assertNotNull($api->oauth->storage);
		$this->assertSame($api->client_id, $api->oauth->client_id);
		$this->assertNotNull($api->cache->storage);
		$this->assertIsArray($api->cache->ttl);
	}

	public function testPaginateValidByTotalItemsAndLoadShortCircuit(): void
	{
		$api = $this->makeStubApiClient();
		$query = new StubQuery($api);
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
		$paginate = new Paginate($query, $api->contacts());
		$paginate->fetchPage();
		$this->assertFalse($paginate->valid()); // models_loaded >= total_items

		$ref = new \ReflectionMethod($paginate, 'load');
		$ref->setAccessible(true);
		$this->assertSame($paginate, $ref->invoke($paginate)); // models уже есть
	}

	public function testModelSaveFalsePaths(): void
	{
		$api = $this->makeStubApiClient();
		$contact = $api->contacts()->create(['name' => 'New']);
		$ref = new \ReflectionClass($contact);
		$prop = $ref->getProperty('service');
		$prop->setAccessible(true);
		$inner = $prop->getValue($contact);
		$prop->setValue($contact, new class ($inner) {
			private $inner;
			public function __construct($inner)
			{
				$this->inner = $inner;
			}
			public function add($data)
			{
				return null;
			}
			public function update($id, $data = null)
			{
				return null;
			}
			public function __get($n)
			{
				return $this->inner->$n;
			}
			public function __call($n, $a)
			{
				return $this->inner->$n(...$a);
			}
		});
		$this->assertFalse($contact->save());

		$existing = $api->contacts()->create(['id' => 5, 'name' => 'Old']);
		$prop->setValue($existing, $prop->getValue($contact));
		$existing->name = 'X';
		$this->assertFalse($existing->save());
	}

	public function testEntitiesAndLinksSaveFalse(): void
	{
		$api = $this->makeStubApiClient();
		$service = $api->contacts();
		$fake = new class ($service) {
			private $inner;
			public $entity_key = 'contacts';
			public function __construct($inner)
			{
				$this->inner = $inner;
			}
			public function getQueryArg($k)
			{
				return $this->inner->getQueryArg($k);
			}
			public function add($data)
			{
				return null;
			}
			public function update($id, $data = null)
			{
				return null;
			}
			public function __get($n)
			{
				return $this->inner->$n;
			}
		};

		$model = $api->contacts()->create(['name' => 'N']);
		$mref = new \ReflectionClass($model);
		$ms = $mref->getProperty('service');
		$ms->setAccessible(true);
		$ms->setValue($model, $fake);
		$col = new Entities([$model]);
		$this->assertFalse($col->save());

		$link = $api->links('leads', 1)->create([
			'entity_id' => 1,
			'to_entity_id' => 2,
			'to_entity_type' => 'contacts',
		]);
		$ms->setValue($link, new class ($api->links('leads', 1)) {
			private $inner;
			public function __construct($inner)
			{
				$this->inner = $inner;
			}
			public function add($data)
			{
				return null;
			}
			public function __get($n)
			{
				return $this->inner->$n;
			}
		});
		$links = new Links([$link]);
		$this->assertFalse($links->save());
	}

	public function testFilesUploadFopenFailAndEmptyFile(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');

		try {
			$api->files()->upload('amofail://blocked.txt', ['content_type' => 'text/plain']);
			$this->fail('expected RuntimeException');
		} catch (\RuntimeException $e) {
			$this->assertStringContainsString('Unable to open file', $e->getMessage());
		}

		$path = sys_get_temp_dir() . '/amoapi-empty-' . uniqid('', true) . '.txt';
		file_put_contents($path, '');
		try {
			$api->files()->upload($path);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('requires file_name', $e->getMessage());
		} finally {
			@unlink($path);
		}
	}

	public function testServiceUpdateClearsCacheKeys(): void
	{
		$api = $this->makeStubApiClient();
		$storage = new \Ufee\AmoV4\Api\Cache\AbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);
		$storage->set('pipelines', (object) ['p' => 1], 60);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'pipelines' => [
					(object) ['id' => 1, 'name' => 'P', 'request_id' => '0'],
				],
			],
		]);
		$api->pipelines()->update([
			(object) ['id' => 1, 'name' => 'P2'],
		]);
		$this->assertNull($storage->get('pipelines'));
	}
}

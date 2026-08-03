<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Coverage;

use Ufee\AmoV4\Api\Cache\AbstractStorage as CacheAbstractStorage;
use Ufee\AmoV4\Api\Cache\FileStorage;
use Ufee\AmoV4\Exceptions\AmoException;
use Ufee\AmoV4\Exceptions\ValidatorException;
use Ufee\AmoV4\Models\File;
use Ufee\AmoV4\Tests\Support\ResponseFactory;
use Ufee\AmoV4\Tests\TestCase;

class CoverageRound4Test extends TestCase
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

	private function stubDriveApi()
	{
		$api = $this->makeStubApiClient();
		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');
		return $api;
	}

	public function testServiceQueryHelpersAndWith(): void
	{
		$api = $this->makeStubApiClient();
		$service = $api->contacts()
			->maxPageRows(7)
			->orderBy('created_at', 'desc')
			->with(['leads'])
			->setQueryArg('page', 2);

		$this->assertSame(7, $service->getQueryArg('limit'));
		$this->assertSame(['created_at' => 'desc'], $service->getQueryArg('order'));
		$this->assertSame('leads', $service->getQueryArg('with'));
		$this->assertSame(2, $service->getQueryArg('page'));

		$service->setQueryArgs(['limit' => 3]);
		$this->assertSame(3, $service->getQueryArg('limit'));
		$this->assertNull($service->getQueryArg('with'));

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [(object) ['id' => 1, 'name' => 'A']],
			],
		]);
		$api->pushResponse(204, '');
		$page = $api->contacts()->paginate(['company'])->fetchAll();
		$this->assertCount(1, $page);
		$this->assertSame('company', $api->lastQuery->args['with']);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [(object) ['id' => 2, 'name' => 'B']],
			],
		]);
		$api->pushResponse(204, '');
		$search = $api->contacts()->search('phone', ['leads'])->fetchAll();
		$this->assertCount(1, $search);
		$this->assertSame('leads', $api->lastQuery->args['with']);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Invalid Service field');
		$service->nope;
	}

	public function testFilesUploadResourceDriveHostAndPayloads(): void
	{
		$api = $this->stubDriveApi();

		// drive_url без схемы → preg_replace host
		$api->setParam('drive_url', 'drive-plain.amocrm.ru/');
		$this->assertSame('drive-plain.amocrm.ru', $api->files()->getDriveHost());

		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');
		$path = sys_get_temp_dir() . '/amoapi-res-' . uniqid('', true) . '.bin';
		file_put_contents($path, '12345678');
		$handle = fopen($path, 'rb');
		try {
			$api->pushResponse(200, [
				'upload_url' => 'https://drive-b.amocrm.ru/upload/p',
				'max_part_size' => 4,
			]);
			$api->pushResponse(200, ['next_url' => 'https://drive-b.amocrm.ru/upload/p2']);
			$api->pushResponse(200, ['uuid' => 'from-res', 'name' => 'r.bin']);
			$file = $api->files()->upload($handle, [
				'file_name' => 'r.bin',
				'file_size' => 8,
				'file_uuid' => 'keep-uuid',
				'with_preview' => true,
			]);
			$this->assertSame('from-res', $file->uuid);
		} finally {
			if (is_resource($handle)) {
				fclose($handle);
			}
			@unlink($path);
		}

		$api->pushResponse(200, [
			'upload_url' => 'https://drive-b.amocrm.ru/upload/bad',
			'max_part_size' => 0,
		]);
		try {
			$api->files()->upload('x', ['file_name' => 'x.txt']);
			$this->fail('expected AmoException');
		} catch (AmoException $e) {
			$this->assertStringContainsString('max_part_size', $e->getMessage());
		}

		try {
			$api->files()->upload(123);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('expects file path', $e->getMessage());
		}

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'files' => [(object) ['uuid' => 'u', 'name' => 'f']],
			],
		]);
		$api->pushResponse(204, '');
		$files = $api->files()->paginate(['versions'])->fetchAll();
		$this->assertCount(1, $files);
		$this->assertSame('versions', $api->lastQuery->args['with']);

		$api->pushResponse(204, '');
		$this->assertCount(0, $api->files()->restore('gone'));

		$api->pushResponse(204, '');
		$this->assertCount(0, $api->files()->getByEntity('leads', 1, ['limit' => 5]));
		$this->assertSame(5, $api->lastQuery->args['limit']);

		$api->pushResponse(204, '');
		$this->assertTrue($api->files()->delete((object) ['uuid' => 'obj-u']));

		$api->pushResponse(204, '');
		$this->assertTrue($api->files()->delete([['uuid' => 'arr-u']]));

		$ref = new \ReflectionMethod($api->files(), 'normalizeFileUuidPayload');
		$ref->setAccessible(true);
		$this->assertSame(
			[['file_uuid' => 'a']],
			$ref->invoke($api->files(), (object) ['file_uuid' => 'a'])
		);
		$this->assertSame(
			[['file_uuid' => 'b']],
			$ref->invoke($api->files(), (object) ['uuid' => 'b'])
		);

		try {
			$api->files()->delete([]);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('can not be empty', $e->getMessage());
		}

		$this->expectException(\InvalidArgumentException::class);
		$api->files()->update('', ['name' => 'x']);
	}

	public function testFilesDriveUrlEmptyThrows(): void
	{
		$api = $this->makeStubApiClient();
		$storage = new CacheAbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);
		$api->pushResponse(200, [
			'id' => 1,
			'name' => 'A',
			'_embedded' => (object) [
				'users_groups' => [],
				'task_types' => [],
			],
		]);
		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('drive_url is empty');
		$api->files()->getDriveHost();
	}

	public function testFileModelEdgeCases(): void
	{
		$api = $this->stubDriveApi();
		$noUuid = $api->files()->create(['name' => 'n']);
		$this->assertFalse($noUuid->delete());
		$this->assertFalse($noUuid->restore());

		try {
			$noUuid->save();
			$this->fail('expected AmoException');
		} catch (AmoException $e) {
			$this->assertStringContainsString('cannot be created via save()', $e->getMessage());
		}

		$file = $api->files()->create([
			'uuid' => 'u',
			'name' => 'f',
			'_links' => ['download' => 'plain'],
		]);
		$this->assertNull($file->getDownloadUrl());

		$api->pushResponse(204, '');
		$this->assertFalse($file->restore());

		try {
			$file->getChangedRawData(['missing']);
			$this->fail('expected AmoException');
		} catch (AmoException $e) {
			$this->assertStringContainsString('required field', $e->getMessage());
		}
	}

	public function testMainContactHelpers(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create([
			'id' => 1,
			'name' => 'L',
			'_embedded' => [
				'contacts' => [
					(object) ['id' => 10, 'is_main' => false],
					(object) ['id' => 11, 'is_main' => true],
				],
			],
		]);
		$this->assertTrue($lead->hasMainContact());

		$api->pushResponse(200, ['id' => 11, 'name' => 'Main']);
		$main = $lead->getMainContact();
		$this->assertSame(11, $main->id);

		$empty = $api->leads()->create(['id' => 2, 'name' => 'E']);
		$this->assertFalse($empty->hasMainContact());
		$this->assertNull($empty->getMainContact());
	}

	public function testResponseValidationBranches(): void
	{
		$api = $this->makeApiClient();
		$api->oauth->setLongToken('t');

		$response = ResponseFactory::make($api, [
			'_embedded' => (object) ['contacts' => []],
		], 200);
		try {
			$response->validatedEntities('contacts');
			$this->fail('expected AmoException');
		} catch (AmoException $e) {
			$this->assertStringContainsString('contacts not found', $e->getMessage());
		}

		$response = ResponseFactory::make($api, [
			'id' => 1,
			'detail' => 'bad',
			'validation-errors' => [
				(object) ['errors' => [(object) ['code' => 'x']]],
			],
		], 200);
		try {
			$response->validatedUpdatedEntity(1);
			$this->fail('expected ValidatorException');
		} catch (ValidatorException $e) {
			$this->assertStringContainsString('bad', $e->getMessage());
		}

		$response = ResponseFactory::make($api, [
			'detail' => 'batch',
			'validation-errors' => [
				(object) ['errors' => [(object) ['code' => 'y']]],
			],
			'_embedded' => (object) ['contacts' => [(object) ['id' => 1]]],
		], 200);
		try {
			$response->validatedUpdatedEntities('contacts');
			$this->fail('expected ValidatorException');
		} catch (ValidatorException $e) {
			$this->assertStringContainsString('batch', $e->getMessage());
		}

		$response = ResponseFactory::make($api, 'ok', 0);
		$ref = new \ReflectionClass($response);
		$error = $ref->getProperty('error');
		$error->setAccessible(true);
		$error->setValue($response, 'curl fail');
		$this->assertSame('curl fail', $response->getError());
	}

	public function testCacheFileStorageDiskReadAndSerializeOptions(): void
	{
		$api = $this->makeApiClient(['domain' => 'disk-cache']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-disk-cache-' . uniqid('', true);

		$storage = new FileStorage($api, ['path' => $this->tempDir]);
		$storage->initialize();
		$payload = (object) ['id' => 9];
		$storage->set('item', $payload, 60);

		$ref = new \ReflectionClass(CacheAbstractStorage::class);
		$local = $ref->getProperty('_local');
		$local->setAccessible(true);
		$all = $local->getValue(null);
		$key = 'disk-cache_' . $api->client_id;
		unset($all[$key]['item']);
		$local->setValue(null, $all);

		$this->assertTrue($storage->has('item'));
		$this->assertSame(9, $storage->get('item')->id);

		$path = $this->tempDir . '/disk-cache/' . $api->client_id . '-stale.cache';
		file_put_contents($path, serialize([
			'expire_at' => time() - 10,
			'payload' => (object) ['id' => 1],
		]));
		$all = $local->getValue(null);
		unset($all[$key]['stale']);
		$local->setValue(null, $all);
		$this->assertNull($storage->get('stale'));
		$this->assertFileDoesNotExist($path);
	}

	public function testApiClientEdgesAndModelHelpers(): void
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Incorrect amoCRM oauth data');
		\Ufee\AmoV4\ApiClient::setInstance([]);
	}

	public function testApiClientMoreEdges(): void
	{
		$api = $this->makeApiClient();
		$this->assertSame('default', $api->getParam('missing', 'default'));
		$api->setIntegration('client_secret', 'new-secret');
		$this->assertSame('new-secret', $api->getIntegration('client_secret'));
		$api->setIntegration('unknown_key', 'x');
		$this->assertNull($api->unknown_prop);
		$this->assertSame($api->client_id, $api->getIntegration('id'));

		try {
			$api->notAService();
			$this->fail('expected Exception');
		} catch (\Exception $e) {
			$this->assertStringContainsString('Invalid service', $e->getMessage());
		}

		try {
			\Ufee\AmoV4\ApiClient::getInstance('missing-id');
			$this->fail('expected Exception');
		} catch (\Exception $e) {
			$this->assertStringContainsString('Account not found', $e->getMessage());
		}

		$contact = $api->contacts()->create(['id' => 1, 'name' => 'A']);
		$contact->setChanged('name');
		$contact->setChanged('name');
		$this->assertTrue($contact->hasChanged('name'));
		$contact->set('name', 'B');
		$this->assertSame('B', $contact->name);
		$arr = $contact->toArray();
		$this->assertSame('B', $arr['name']);
		$this->assertSame('Contact', $contact::getBasename());
	}

	public function testServiceCacheClearOnAddAndApiPropertyGet(): void
	{
		$api = $this->makeStubApiClient();
		$storage = new CacheAbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);
		$storage->set('pipelines', (object) ['p' => 1], 60);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'pipelines' => [(object) ['id' => 1, 'name' => 'P', 'request_id' => '0']],
			],
		]);
		$api->pipelines()->add([(object) ['name' => 'P', 'request_id' => '0']]);
		$this->assertNull($storage->get('pipelines'));

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'users' => [(object) ['id' => 1, 'name' => 'U']],
			],
		]);
		$api->pushResponse(204, '');
		$users = $api->users;
		$this->assertCount(1, $users);

		$api->pushResponse(200, ['id' => 5, 'name' => 'El', 'catalog_id' => 3]);
		$el = $api->catalogElement(3, 5);
		$this->assertSame(5, $el->id);
	}

	public function testOauthRefreshNonJsonAndUnlockOnError(): void
	{
		$api = $this->makeStubApiClient();
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 3600,
			'access_token' => 'a',
			'refresh_token' => 'r',
			'created_at' => time(),
		]);
		$unlocked = false;
		$api->callbacks->on('oauth.token.refresh.unlock', function () use (&$unlocked) {
			$unlocked = true;
		});
		$api->pushResponse(500, 'not-json-body');
		try {
			$api->oauth->refreshToken();
			$this->fail('expected AmoException');
		} catch (AmoException $e) {
			$this->assertStringContainsString('non JSON', $e->getMessage());
		}
		$this->assertTrue($unlocked);
	}
}

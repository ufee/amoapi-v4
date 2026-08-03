<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Coverage;

use Ufee\AmoV4\Api\Cache\FileStorage;
use Ufee\AmoV4\Api\Paginate;
use Ufee\AmoV4\Collections\Links;
use Ufee\AmoV4\Exceptions\AmoException;
use Ufee\AmoV4\Exceptions\RequestException;
use Ufee\AmoV4\Models\File;
use Ufee\AmoV4\Tests\Support\LocalHttpServer;
use Ufee\AmoV4\Tests\Support\StubQuery;
use Ufee\AmoV4\Tests\TestCase;

class CoverageRound5Test extends TestCase
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

	public function testTasksNotesSubscriptionsTraits(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create(['id' => 10, 'name' => 'L']);

		$api->pushResponse(200, [
			'id' => 7,
			'text' => 'Task',
			'entity_id' => 10,
			'entity_type' => 'leads',
		]);
		$task = $lead->findTask(7);
		$this->assertSame(7, $task->id);

		$paginate = $lead->getTasks(['is_completed' => 0]);
		$this->assertInstanceOf(Paginate::class, $paginate);
		$this->assertSame(10, $paginate->query->args['filter']['entity_id']);
		$this->assertSame('leads', $paginate->query->args['filter']['entity_type']);

		$api->pushResponse(200, [
			'id' => 3,
			'note_type' => 'common',
			'params' => (object) ['text' => 'n'],
			'entity_id' => 10,
		]);
		$note = $lead->findNote(3);
		$this->assertSame(3, $note->id);

		$notesPage = $lead->getNotes(['note_type' => 'common']);
		$this->assertInstanceOf(Paginate::class, $notesPage);
		$this->assertSame('is_pinned', $notesPage->query->args['with']);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'subscriptions' => [
					(object) ['subscriber_id' => 1, 'entity_id' => 10],
				],
			],
		]);
		$api->pushResponse(204, '');
		$subs = $lead->getSubscriptions();
		$this->assertCount(1, $subs);
	}

	public function testLinkedContactsBatchAndList(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create(['id' => 50, 'name' => 'L']);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 50,
						'to_entity_id' => 1,
						'to_entity_type' => 'contacts',
						'request_id' => '1_contacts',
					],
					(object) [
						'entity_id' => 50,
						'to_entity_id' => 2,
						'to_entity_type' => 'contacts',
						'request_id' => '2_contacts',
					],
				],
			],
		]);
		$links = $lead->attachContacts([1, 2]);
		$this->assertInstanceOf(Links::class, $links);

		$api->pushResponse(204, '');
		$this->assertTrue($lead->detachContacts([1, 2]));

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 50,
						'to_entity_id' => 1,
						'to_entity_type' => 'contacts',
					],
				],
			],
		]);
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [(object) ['id' => 1, 'name' => 'C']],
			],
		]);
		$api->pushResponse(204, '');
		$contacts = $lead->contacts();
		$this->assertNotFalse($contacts);
		$this->assertSame(1, $contacts->first()->id);
	}

	public function testFilesTraitAttachWithFileModelAndUuidKey(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');
		$lead = $api->leads()->create(['id' => 1, 'name' => 'L']);
		$file = $api->files()->create(['uuid' => 'file-1', 'name' => 'a.txt']);

		$api->pushResponse(202, '');
		$this->assertTrue($lead->attachFiles([
			$file,
			['uuid' => 'file-2'],
		]));
		$this->assertStringContainsString('/leads/1/files', $api->lastQuery->url);
	}

	public function testFilesUploadEdgesAndPayloads(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');
		$path = sys_get_temp_dir() . '/amoapi-meta-' . uniqid('', true) . '.txt';
		file_put_contents($path, 'abcdef');
		$handle = fopen($path, 'rb');

		try {
			// resource без file_name/file_size — берёт из meta uri
			$api->pushResponse(200, [
				'upload_url' => 'https://drive-b.amocrm.ru/u',
				'max_part_size' => 100,
			]);
			$api->pushResponse(200, ['uuid' => 'meta-u', 'name' => basename($path)]);
			$file = $api->files()->upload($handle);
			$this->assertSame('meta-u', $file->uuid);
		} finally {
			if (is_resource($handle)) {
				fclose($handle);
			}
			@unlink($path);
		}

		try {
			$api->files()->upload('', ['file_name' => '']);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('requires file_name', $e->getMessage());
		}

		$api->pushResponse(200, [
			'upload_url' => 'https://drive-b.amocrm.ru/u2',
			'max_part_size' => 10,
		]);
		$api->pushResponse(200, ['next_url' => 'https://drive-b.amocrm.ru/u3']);
		try {
			$api->files()->upload('hello', ['file_name' => 'h.txt']);
			$this->fail('expected AmoException');
		} catch (AmoException $e) {
			$this->assertStringContainsString('file model not returned', $e->getMessage());
		}

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'files' => [(object) ['uuid' => 'f', 'name' => 'n']],
			],
		]);
		$api->pushResponse(204, '');
		$page = $api->files()->filter(['term' => 'n'], ['versions'])->fetchAll();
		$this->assertCount(1, $page);
		$this->assertSame('versions', $api->lastQuery->args['with']);

		try {
			$api->files()->update('u', null);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('must be object or array', $e->getMessage());
		}

		$ref = new \ReflectionMethod($api->files(), 'normalizeFileUuidPayload');
		$ref->setAccessible(true);
		$this->assertSame(
			[['file_uuid' => 'a'], ['file_uuid' => 'b']],
			$ref->invoke($api->files(), [['file_uuid' => 'a'], ['uuid' => 'b']])
		);
		try {
			$ref->invoke($api->files(), []);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('can not be empty', $e->getMessage());
		}
		try {
			$ref->invoke($api->files(), [123]);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('file_uuid or uuid', $e->getMessage());
		}

		$ref2 = new \ReflectionMethod($api->files(), 'normalizeUuidPayload');
		$ref2->setAccessible(true);
		try {
			$ref2->invoke($api->files(), [new \stdClass()]);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('must contain uuid', $e->getMessage());
		}
	}

	public function testPaginateLimitsAndModelEdges(): void
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
		$paginate->maxPages(1)->fetchPage();
		$this->assertFalse($paginate->hasNext());
		$this->assertFalse($paginate->valid());
		// повторный fetchPage — models уже загружены
		$this->assertCount(1, $paginate->fetchPage());

		$contact = $api->contacts()->create([
			'id' => 1,
			'name' => 'C',
			'_embedded' => [
				'companies' => [(object) ['id' => 9]],
			],
		]);
		$arr = $contact->toArray();
		$this->assertArrayHasKey('companies', $arr);

		$note = $api->leads()->notes()->create(['id' => 1, 'note_type' => 'common']);
		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('required field');
		$note->getChangedRawData();
	}

	public function testLinksCatalogElementsEmptyAndServiceFilterWith(): void
	{
		$api = $this->makeStubApiClient();
		$links = new Links([]);
		$this->assertFalse($links->catalogElements());

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [(object) ['id' => 1, 'name' => 'A']],
			],
		]);
		$api->pushResponse(204, '');
		$result = $api->contacts()->filter(['name' => 'A'], ['leads'])->fetchAll();
		$this->assertCount(1, $result);
		$this->assertSame('leads', $api->lastQuery->args['with']);
	}

	public function testCacheFileStorageSerializeValidation(): void
	{
		$api = $this->makeApiClient(['domain' => 'ser']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-ser-' . uniqid('', true);
		mkdir($this->tempDir, 0777, true);

		try {
			new FileStorage($api, ['path' => $this->tempDir, 'serialize' => 123]);
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('serialize', $e->getMessage());
		}

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('unserialize');
		new FileStorage($api, ['path' => $this->tempDir, 'unserialize' => 123]);
	}

	public function testOauthRefreshRequestException(): void
	{
		$api = $this->makeStubApiClient();
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 3600,
			'access_token' => 'a',
			'refresh_token' => 'r',
			'created_at' => time(),
		]);
		$api->queryClass = ThrowingPostQuery::class;

		$this->expectException(RequestException::class);
		$this->expectExceptionMessage('network boom');
		$api->oauth->refreshToken();
	}

	public function testQueryExecuteFalseAndInterfaceAndInvalidSet(): void
	{
		$this->server = new LocalHttpServer();
		$api = $this->makeApiClient();
		$api->oauth->setLongToken('tok');
		$api->setParam('query_delay', 0);
		$api->callbacks->off('query.response.code');
		$api->callbacks->on('query.response.code', function () {
			return false;
		});

		$query = $api->query('GET', $this->server->url('/x'));
		$api->queries->viaInterfaces(['127.0.0.1']);
		$query->prepare();
		$this->assertFalse($query->execute());

		try {
			$query->not_a_field = 1;
			$this->fail('expected Exception');
		} catch (\Exception $e) {
			$this->assertStringContainsString('Invalid Query field', $e->getMessage());
		}
	}

	public function testFileSaveUpdateFalse(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');
		$file = $api->files()->create(['uuid' => 'u', 'name' => 'n']);
		$file->name = 'x';
		$api->pushResponse(204, '');
		// validated() на 204/пустом теле бросит — ловим как fail path через reflection update false
		$service = $api->files();
		$ref = new \ReflectionClass($file);
		$svc = $ref->getProperty('service');
		$svc->setAccessible(true);
		$fake = new class ($service) {
			private $inner;
			public function __construct($inner)
			{
				$this->inner = $inner;
			}
			public function update($uuid, $data = null)
			{
				return null;
			}
			public function __call($n, $a)
			{
				return $this->inner->$n(...$a);
			}
		};
		$svc->setValue($file, $fake);
		$this->assertFalse($file->save());
		$this->assertNull($file->getDownloadUrl()); // нет download в links — line 153
	}

	public function testCacheAbstractStorageSetWithoutInit(): void
	{
		$api = $this->makeApiClient(['domain' => 'lazy-set']);
		$storage = new \Ufee\AmoV4\Api\Cache\AbstractStorage($api, []);
		// clear local key полностью
		$ref = new \ReflectionClass(\Ufee\AmoV4\Api\Cache\AbstractStorage::class);
		$local = $ref->getProperty('_local');
		$local->setAccessible(true);
		$all = $local->getValue(null);
		unset($all['lazy-set_' . $api->client_id]);
		$local->setValue(null, $all);

		$this->assertTrue($storage->set('k', (object) ['v' => 1], 10));
		$this->assertSame(1, $storage->get('k')->v);
	}

	public function testApiClientGetIntegrationAll(): void
	{
		$api = $this->makeApiClient();
		$all = $api->getIntegration();
		$this->assertIsArray($all);
		$this->assertArrayHasKey('client_id', $all);
	}

	public function testCollectionFindCallableDuplicateAndGroupByArray(): void
	{
		$c = new Links([
			['id' => 1, 'g' => 'a'],
			['id' => 2, 'g' => 'a'],
			['id' => 3, 'g' => 'b'],
		]);
		$found = $c->find(function ($item) {
			return $item['id'] === 2;
		});
		$this->assertCount(1, $found);
		$grouped = $c->groupBy('g');
		$this->assertCount(2, $grouped->get('a'));
		$this->assertCount(2, $grouped); // groups a,b — covers array branch groupBy
	}
}

/**
 * StubQuery, у которого post() бросает — для Oauth refresh catch.
 */
class ThrowingPostQuery extends StubQuery
{
	public function post()
	{
		throw new \RuntimeException('network boom');
	}
}

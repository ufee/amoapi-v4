<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Coverage;

use Ufee\AmoV4\Api\Cache\AbstractStorage;
use Ufee\AmoV4\Collections\Files;
use Ufee\AmoV4\Collections\Pipelines;
use Ufee\AmoV4\Models\Company;
use Ufee\AmoV4\Models\FileVersion;
use Ufee\AmoV4\Models\PipelineStatus;
use Ufee\AmoV4\Tests\TestCase;

class CoverageRound2Test extends TestCase
{
	private function seedCache($api): AbstractStorage
	{
		$storage = new AbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);
		return $storage;
	}

	public function testBotsFindRunStopViaStub(): void
	{
		$api = $this->makeStubApiClient();

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'items' => [(object) ['id' => 5, 'name' => 'Bot']],
			],
		]);
		$api->pushResponse(204, '');
		$bot = $api->bots()->find(5);
		$this->assertSame(5, $bot->id);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) ['items' => []],
		]);
		// пустая страница: fetchAll не запрашивает следующую
		$this->assertNull($api->bots()->find(99));

		$api->pushResponse(202, '');
		$this->assertTrue($api->bots()->run(5, 10, 'leads'));
		$this->assertStringEndsWith('/bots/run', $api->lastQuery->url);

		$api->pushResponse(202, '');
		$this->assertTrue($api->bots()->run([
			['bot_id' => 5, 'entity_id' => 11, 'entity_type' => 'contacts'],
		]));

		$api->pushResponse(202, '');
		$this->assertTrue($api->bots()->stop(5, 10, 'leads'));
		$this->assertStringEndsWith('/bots/5/stop', $api->lastQuery->url);
	}

	public function testSourcesRemoveViaStub(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(204, '');
		$this->assertTrue($api->sources()->remove(7));
		$this->assertStringEndsWith('/sources/7', $api->lastQuery->url);

		$api->pushResponse(204, '');
		$this->assertTrue($api->sources()->remove([7, 8]));
		$this->assertSame('DELETE', $api->lastQuery->method);

		$api->pushResponse(400, ['detail' => 'no']);
		$this->assertFalse($api->sources()->remove([9]));
	}

	public function testNotesPinUnpinViaStub(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(204, '');
		$this->assertTrue($api->notes('contacts')->pin(15));
		$this->assertStringEndsWith('/notes/15/pin', $api->lastQuery->url);

		$api->pushResponse(204, '');
		$this->assertTrue($api->notes('contacts')->unpin(15));
		$this->assertStringEndsWith('/notes/15/unpin', $api->lastQuery->url);
	}

	public function testPipelineStatusNavigation(): void
	{
		$api = $this->makeApiClient();
		$storage = $this->seedCache($api);
		$pipeline = $api->pipelines()->create([
			'id' => 1,
			'name' => 'P',
			'_embedded' => [
				'statuses' => [
					(object) ['id' => 10, 'name' => 'New', 'sort' => 10, 'pipeline_id' => 1],
					(object) ['id' => 20, 'name' => 'Work', 'sort' => 20, 'pipeline_id' => 1],
					(object) ['id' => 142, 'name' => 'Won', 'sort' => 10000, 'pipeline_id' => 1],
					(object) ['id' => 143, 'name' => 'Lost', 'sort' => 11000, 'pipeline_id' => 1],
				],
			],
		]);
		$storage->set('pipelines', new Pipelines([$pipeline]), 60);

		/** @var PipelineStatus $status */
		$status = $api->pipelineStatuses(1)->create([
			'id' => 20,
			'name' => 'Work',
			'sort' => 20,
			'pipeline_id' => 1,
		]);
		$this->assertSame(1, $status->pipeline()->id);
		$this->assertSame(142, $status->next()->id);
		$this->assertSame(10, $status->previous()->id);
	}

	public function testFileVersionDownloadUrl(): void
	{
		$api = $this->makeApiClient();
		$v = new FileVersion([
			'uuid' => 'v1',
			'_links' => ['download' => (object) ['href' => 'https://d/v']],
		], $api->files());
		$this->assertSame('https://d/v', $v->getDownloadUrl());

		$v2 = new FileVersion(['uuid' => 'v2', '_links' => ['download' => ['href' => 'https://d/a']]], $api->files());
		$this->assertSame('https://d/a', $v2->getDownloadUrl());

		$v3 = new FileVersion(['uuid' => 'v3'], $api->files());
		$this->assertNull($v3->getDownloadUrl());
	}

	public function testLeadCreateContactWithCompanyAndCreateCompany(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create([
			'id' => 100,
			'name' => 'L',
			'responsible_user_id' => 1,
			'_embedded' => [
				'companies' => [(object) ['id' => 9]],
				'contacts' => [(object) ['id' => 8, 'is_main' => true]],
			],
		]);

		// createContact: attachLead + attachCompany
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [(object) ['entity_id' => null, 'to_entity_id' => 100, 'to_entity_type' => 'leads']],
			],
		]);
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [(object) ['entity_id' => null, 'to_entity_id' => 9, 'to_entity_type' => 'companies']],
			],
		]);
		$contact = $lead->createContact();
		$this->assertSame(1, $contact->responsible_user_id);

		// createCompany: attachLead → getMainContact(find) → attachContact
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [(object) [
					'entity_id' => null,
					'to_entity_id' => 100,
					'to_entity_type' => 'leads',
				]],
			],
		]);
		$api->pushResponse(200, ['id' => 8, 'name' => 'Main']);
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [(object) [
					'entity_id' => null,
					'to_entity_id' => 8,
					'to_entity_type' => 'contacts',
				]],
			],
		]);
		$company = $lead->createCompany();
		$this->assertInstanceOf(Company::class, $company);
	}

	public function testTagsHelpersExtended(): void
	{
		$contact = $this->service('contacts')->create([
			'id' => 1,
			'name' => 'C',
			'_embedded' => [
				'tags' => [(object) ['id' => 1, 'name' => 'Old']],
			],
		]);
		$contact->attachTag(['name' => 'Color', 'color' => 'FF8F92']);
		$contact->attachTags([10, 'Named']);
		$this->assertNotEmpty($contact->getTags());
		$contact->detachTags(['Old']);
		$contact->setTags([1, 2]);
		$payload = $contact->getChangedRawData();
		$this->assertArrayHasKey('tags_to_add', (array) $payload);
		$this->assertArrayHasKey('tags_to_delete', (array) $payload);
	}

	public function testCatalogElementsBatchAndList(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create(['id' => 1, 'name' => 'L']);
		$elements = $api->catalogElements(7)->createCollection([
			['id' => 10, 'name' => 'A', 'catalog_id' => 7],
			['id' => 11, 'name' => 'B', 'catalog_id' => 7],
		]);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) ['entity_id' => 1, 'to_entity_id' => 10, 'to_entity_type' => 'catalog_elements', 'request_id' => '10_catalog_elements'],
					(object) ['entity_id' => 1, 'to_entity_id' => 11, 'to_entity_type' => 'catalog_elements', 'request_id' => '11_catalog_elements'],
				],
			],
		]);
		$this->assertNotFalse($lead->attachCatalogElements($elements));

		$api->pushResponse(204, '');
		$this->assertTrue($lead->detachCatalogElements([10, 11], 7));

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 1,
						'to_entity_id' => 10,
						'to_entity_type' => 'catalog_elements',
						'metadata' => (object) ['catalog_id' => 7],
					],
				],
			],
		]);
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'elements' => [(object) ['id' => 10, 'name' => 'A', 'catalog_id' => 7]],
			],
		]);
		$api->pushResponse(204, '');
		$list = $lead->catalogElements(7);
		$this->assertNotFalse($list);
		$this->assertSame(10, $list->first()->id);
	}

	public function testEventsTypesDefaultLangAndLinksEmptyEmbedded(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('lang', 'es');
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'events_types' => [(object) ['key' => 'x', 'name' => 'X']],
			],
		]);
		$types = $api->events()->types();
		$this->assertSame('es', $api->lastQuery->args['language_code']);
		$this->assertCount(1, $types);

		$api->pushResponse(200, ['_embedded' => (object) []]);
		$this->assertCount(0, $api->links('contacts', 1)->get());
	}

	public function testSearchSkipsModelsWithoutCustomField(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 1, 'name' => 'NoCF'],
					(object) [
						'id' => 2,
						'name' => 'With',
						'custom_fields_values' => [
							(object) [
								'field_id' => 1,
								'field_name' => 'Email',
								'field_code' => 'EMAIL',
								'field_type' => 'multitext',
								'values' => [(object) ['value' => 'a@example.com']],
							],
						],
					],
				],
			],
		]);
		$api->pushResponse(204, '');
		$result = $api->contacts()->searchByEmail('a@example.com', 1);
		$this->assertCount(1, $result);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 1, 'name' => 'NoCF'],
					(object) [
						'id' => 2,
						'name' => 'With',
						'custom_fields_values' => [
							(object) [
								'field_id' => 2,
								'field_name' => 'Город',
								'field_code' => null,
								'field_type' => 'text',
								'values' => [(object) ['value' => 'Москва']],
							],
						],
					],
				],
			],
		]);
		$api->pushResponse(204, '');
		$result = $api->contacts()->searchByCustomField('Москва', 'Город', 1);
		$this->assertCount(1, $result);
	}

	public function testFilesCollectionEmptyUuidsAndPipelineStatusDeleteFalse(): void
	{
		$api = $this->makeStubApiClient();
		$files = new Files([
			$api->files()->create(['name' => 'no-uuid']),
		]);
		$this->assertFalse($files->delete());
		$this->assertFalse($files->restore());

		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');
		$api->pushResponse(400, ['detail' => 'no']);
		$this->assertFalse($api->pipelineStatuses(1)->delete(99));
	}

	public function testAccountGetWithOverride(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'id' => 1,
			'name' => 'A',
			'_embedded' => (object) [
				'users_groups' => [],
				'task_types' => [],
			],
		]);
		$account = $api->account()->get(['drive_url']);
		$this->assertSame(1, $account->id);
		$this->assertSame('drive_url', $api->lastQuery->args['with']);
	}
}

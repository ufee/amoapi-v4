<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Api\Cache\AbstractStorage;
use Ufee\AmoV4\Tests\TestCase;

class CacheFacadeTest extends TestCase
{
	private function memoryStorage($api): AbstractStorage
	{
		$storage = new AbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);
		return $storage;
	}

	public function testAccountPipelinesUsersHitMiss(): void
	{
		$api = $this->makeStubApiClient();
		$this->memoryStorage($api);

		$api->pushResponse(200, [
			'id' => 1,
			'name' => 'Acc',
			'_embedded' => (object) [
				'users_groups' => [(object) ['id' => 0, 'name' => 'Admins']],
				'task_types' => [(object) ['id' => 1, 'name' => 'Call']],
			],
		]);
		$account1 = $api->cache->account();
		$this->assertSame(1, $account1->id);

		// второй вызов — из cache, без HTTP
		$account2 = $api->cache->account();
		$this->assertSame($account1, $account2);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'pipelines' => [
					(object) [
						'id' => 5,
						'name' => 'Main',
						'_embedded' => (object) [
							'statuses' => [
								(object) ['id' => 50, 'name' => 'New', 'sort' => 10, 'pipeline_id' => 5],
							],
						],
					],
				],
			],
		]);
		$api->pushResponse(204, '');
		$pipelines = $api->cache->pipelines();
		$this->assertCount(1, $pipelines);
		$this->assertSame(5, $api->cache->pipeline(5)->id);
		$this->assertSame($pipelines, $api->cache->pipelines());

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'users' => [
					(object) ['id' => 9, 'name' => 'Ivan'],
				],
			],
		]);
		$api->pushResponse(204, '');
		$users = $api->cache->users();
		$this->assertSame(9, $api->cache->user(9)->id);
		$this->assertSame($users, $api->cache->users());
	}

	public function testCustomFieldsCatalogsSourcesAndEventTypes(): void
	{
		$api = $this->makeStubApiClient();
		$this->memoryStorage($api);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'custom_fields' => [
					(object) ['id' => 11, 'name' => 'City', 'type' => 'text'],
				],
			],
		]);
		$api->pushResponse(204, '');
		$fields = $api->cache->customFields('contacts');
		$this->assertCount(1, $fields);
		$this->assertSame($fields, $api->cache->customFields('contacts'));

		$this->expectException(\InvalidArgumentException::class);
		$api->cache->customFields();
	}

	public function testCatalogsSourcesLossReasonsAndClear(): void
	{
		$api = $this->makeStubApiClient();
		$storage = $this->memoryStorage($api);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'catalogs' => [(object) ['id' => 3, 'name' => 'Products']],
			],
		]);
		$api->pushResponse(204, '');
		$this->assertSame(3, $api->cache->catalog(3)->id);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'sources' => [(object) ['id' => 4, 'name' => 'Form']],
			],
		]);
		$api->pushResponse(204, '');
		$this->assertSame(4, $api->cache->source(4)->id);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'loss_reasons' => [(object) ['id' => 8, 'name' => 'Price']],
			],
		]);
		$api->pushResponse(204, '');
		$this->assertSame(8, $api->cache->lossReason(8)->id);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'events_types' => [(object) ['key' => 'lead_added', 'name' => 'Lead']],
			],
		]);
		$types = $api->cache->eventTypes('ru');
		$this->assertSame('lead_added', $types->first()->key);
		$this->assertSame($types, $api->cache->eventTypes('ru'));

		$api->cache->setTtl(['catalogs' => 10]);
		$api->cache->clear('catalogs');
		$this->assertNull($storage->get('catalogs'));
	}

	public function testUserGroupsAndTaskTypesFromAccountCache(): void
	{
		$api = $this->makeStubApiClient();
		$this->memoryStorage($api);

		$api->pushResponse(200, [
			'id' => 1,
			'name' => 'Acc',
			'_embedded' => (object) [
				'users_groups' => [(object) ['id' => 2, 'name' => 'Sales']],
				'task_types' => [(object) ['id' => 3, 'name' => 'Meet']],
			],
		]);

		$groups = $api->cache->userGroups();
		$this->assertSame('Sales', $groups->first()->name);
		$this->assertSame($groups, $api->cache->userGroups());

		$types = $api->cache->taskTypes();
		$this->assertSame('Meet', $types->first()->name);
		$this->assertSame($types, $api->cache->taskTypes());
	}
}

<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Collections\Links;
use Ufee\AmoV4\Models\Link;
use Ufee\AmoV4\Services\Links as LinksService;
use Ufee\AmoV4\Tests\TestCase;

class LinksServiceTest extends TestCase
{
	public function testServiceMetaAndPath(): void
	{
		$service = $this->service('links', 'contacts', 10);
		$this->assertInstanceOf(LinksService::class, $service);
		$this->assertSame('/api/v4/contacts/10', $service->api_path);
		$this->assertSame('links', $service->entity_key);
	}

	public function testGetAddAndDeleteViaStub(): void
	{
		$api = $this->makeStubApiClient();

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 10,
						'to_entity_id' => 20,
						'to_entity_type' => 'leads',
					],
				],
			],
		]);
		$links = $api->links('contacts', 10)->get(['to_entity_type' => 'leads']);
		$this->assertInstanceOf(Links::class, $links);
		$this->assertCount(1, $links);
		$this->assertStringEndsWith('/contacts/10/links', $api->lastQuery->url);
		$this->assertSame(['to_entity_type' => 'leads'], $api->lastQuery->args['filter']);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 10,
						'to_entity_id' => 21,
						'to_entity_type' => 'leads',
					],
				],
			],
		]);
		$row = $api->links('contacts', 10)->add((object) [
			'to_entity_id' => 21,
			'to_entity_type' => 'leads',
		]);
		$this->assertSame(21, $row->to_entity_id);
		$this->assertStringEndsWith('/link', $api->lastQuery->url);

		$api->pushResponse(204, '');
		$this->assertTrue($api->links('contacts', 10)->delete([
			'to_entity_id' => 21,
			'to_entity_type' => 'leads',
		]));
		$this->assertStringEndsWith('/unlink', $api->lastQuery->url);
	}

	public function testGetWithoutEmbeddedLinksReturnsEmptyCollection(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, ['_embedded' => (object) []]);
		$links = $api->links('leads', 1)->get();
		$this->assertCount(0, $links);
	}

	public function testCollectionSaveAndDelete(): void
	{
		$api = $this->makeStubApiClient();
		$service = $api->links('contacts', 5);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 5,
						'to_entity_id' => 1,
						'to_entity_type' => 'leads',
						'request_id' => '1_leads',
					],
					(object) [
						'entity_id' => 5,
						'to_entity_id' => 2,
						'to_entity_type' => 'leads',
						'request_id' => '2_leads',
					],
				],
			],
		]);
		$collection = $service->createCollection([
			['entity_id' => 5, 'to_entity_id' => 1, 'to_entity_type' => 'leads'],
			['entity_id' => 5, 'to_entity_id' => 2, 'to_entity_type' => 'leads'],
		]);
		$this->assertTrue($collection->save());

		$api->pushResponse(204, '');
		$this->assertTrue($collection->delete());

		$empty = new Links([]);
		$this->assertFalse($empty->save());
		$this->assertFalse($empty->delete());
	}

	public function testCollectionEntitiesHelpers(): void
	{
		$api = $this->makeStubApiClient();
		$service = $api->links('contacts', 1);
		$collection = $service->createCollection([
			[
				'entity_id' => 1,
				'to_entity_id' => 10,
				'to_entity_type' => 'leads',
			],
			[
				'entity_id' => 1,
				'to_entity_id' => 20,
				'to_entity_type' => 'companies',
			],
			[
				'entity_id' => 1,
				'to_entity_id' => 30,
				'to_entity_type' => 'catalog_elements',
				'metadata' => (object) ['catalog_id' => 7],
			],
		]);

		// leads find by ids
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'leads' => [(object) ['id' => 10, 'name' => 'L']],
			],
		]);
		$api->pushResponse(204, '');
		$leads = $collection->leads();
		$this->assertCount(1, $leads);
		$this->assertSame(10, $leads->first()->id);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'companies' => [(object) ['id' => 20, 'name' => 'Co']],
			],
		]);
		$api->pushResponse(204, '');
		$company = $collection->company();
		$this->assertSame(20, $company->id);

		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'elements' => [(object) ['id' => 30, 'name' => 'El', 'catalog_id' => 7]],
			],
		]);
		$api->pushResponse(204, '');
		$elements = $collection->catalogElements();
		$this->assertNotFalse($elements);
		$this->assertSame(30, $elements->first()->id);

		$this->assertFalse(
			$service->createCollection([
				['entity_id' => 1, 'to_entity_id' => 1, 'to_entity_type' => 'contacts'],
			])->leads()
		);
	}
}

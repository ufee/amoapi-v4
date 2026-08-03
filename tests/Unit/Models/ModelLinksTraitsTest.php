<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Models;

use Ufee\AmoV4\Models\CatalogElement;
use Ufee\AmoV4\Models\Link;
use Ufee\AmoV4\Tests\TestCase;

class ModelLinksTraitsTest extends TestCase
{
	public function testAttachAndDetachLeadCompanyContact(): void
	{
		$api = $this->makeStubApiClient();
		$contact = $api->contacts()->create(['id' => 1, 'name' => 'C']);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 1,
						'to_entity_id' => 50,
						'to_entity_type' => 'leads',
					],
				],
			],
		]);
		$link = $contact->attachLead(50);
		$this->assertInstanceOf(Link::class, $link);
		$this->assertStringEndsWith('/contacts/1/link', $api->lastQuery->url);

		$api->pushResponse(204, '');
		$this->assertTrue($contact->detachLead(50));
		$this->assertStringEndsWith('/unlink', $api->lastQuery->url);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 1,
						'to_entity_id' => 60,
						'to_entity_type' => 'companies',
					],
				],
			],
		]);
		$this->assertInstanceOf(Link::class, $contact->attachCompany(60));

		$api->pushResponse(204, '');
		$this->assertTrue($contact->detachCompany(60));

		$lead = $api->leads()->create(['id' => 50, 'name' => 'L']);
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
		$this->assertInstanceOf(Link::class, $lead->attachContact($contact));

		$api->pushResponse(204, '');
		$this->assertTrue($lead->detachContact(1));
	}

	public function testAttachEntitiesBatchAndHasHelpers(): void
	{
		$api = $this->makeStubApiClient();
		$contact = $api->contacts()->create([
			'id' => 1,
			'name' => 'C',
			'_embedded' => [
				'companies' => [(object) ['id' => 9]],
				'leads' => [(object) ['id' => 2], (object) ['id' => 3]],
			],
		]);

		$this->assertTrue($contact->hasCompany());
		$this->assertSame(9, $contact->getCompanyId());
		$this->assertTrue($contact->hasLeads());

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 1,
						'to_entity_id' => 2,
						'to_entity_type' => 'leads',
						'request_id' => '2_leads',
					],
					(object) [
						'entity_id' => 1,
						'to_entity_id' => 3,
						'to_entity_type' => 'leads',
						'request_id' => '3_leads',
					],
				],
			],
		]);
		$batch = $contact->attachLeads([2, 3]);
		$this->assertNotFalse($batch);
		$this->assertCount(2, $batch);

		$api->pushResponse(204, '');
		$this->assertTrue($contact->detachLeads([2, 3]));
	}

	public function testCatalogElementAttachRequiresCatalogId(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create(['id' => 1, 'name' => 'L']);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('catalog_id');
		$lead->attachCatalogElement(10);
	}

	public function testAttachCatalogElementWithCatalogId(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create(['id' => 1, 'name' => 'L']);
		$element = $api->catalogElements(7)->create([
			'id' => 10,
			'name' => 'Item',
			'catalog_id' => 7,
		]);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 1,
						'to_entity_id' => 10,
						'to_entity_type' => 'catalog_elements',
						'metadata' => (object) ['catalog_id' => 7, 'quantity' => 2],
					],
				],
			],
		]);
		$link = $lead->attachCatalogElement($element, null, 2);
		$this->assertInstanceOf(Link::class, $link);
		$payload = $api->lastQuery->json_data;
		$this->assertSame(7, $payload[0]->metadata['catalog_id'] ?? $payload[0]->metadata->catalog_id ?? null);
	}

	public function testDetachCatalogElement(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create(['id' => 1, 'name' => 'L']);

		$api->pushResponse(204, '');
		$this->assertTrue($lead->detachCatalogElement(10, 7));
	}

	public function testCompanyFinderUsesEmbeddedId(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create([
			'id' => 1,
			'name' => 'L',
			'_embedded' => [
				'companies' => [(object) ['id' => 9]],
			],
		]);

		$api->pushResponse(200, ['id' => 9, 'name' => 'Co']);
		$company = $lead->company();
		$this->assertSame(9, $company->id);

		$empty = $api->leads()->create(['id' => 2, 'name' => 'X']);
		$this->assertFalse($empty->company());
		$this->assertFalse($empty->hasCompany());
	}

	public function testFilesTraitAttachDetachGet(): void
	{
		$api = $this->makeStubApiClient();
		$contact = $api->contacts()->create(['id' => 1, 'name' => 'C']);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'files' => [
					(object) ['file_uuid' => 'u-1', 'file_name' => 'a.txt'],
				],
			],
		]);
		$files = $contact->getFiles();
		$this->assertCount(1, $files);
		$this->assertStringEndsWith('/contacts/1/files', $api->lastQuery->url);

		$api->pushResponse(202, '');
		$this->assertTrue($contact->attachFiles('uuid-1'));
		$this->assertSame('PUT', $api->lastQuery->method);

		$api->pushResponse(202, '');
		$this->assertTrue($contact->detachFiles(['uuid-1']));
		$this->assertSame('DELETE', $api->lastQuery->method);
	}
}

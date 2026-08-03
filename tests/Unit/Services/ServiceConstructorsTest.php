<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Tests\TestCase;

class ServiceConstructorsTest extends TestCase
{
	public function testCatalogElementsRequiresCatalogId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('CatalogElements Service required catalog_id');
		$this->service('catalogElements', 0);
	}

	public function testPipelineStatusesRequiresPipelineId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('PipelineStatuses Service required pipeline_id');
		$this->service('pipelineStatuses', 0);
	}

	public function testCustomFieldsRequiresEntity(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('CustomFields Service required entity');
		$this->service('customFields');
	}

	public function testCustomFieldsCatalogsRequiresCatalogId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('required catalog_id');
		$this->service('customFields', 'catalogs');
	}

	public function testCustomFieldsCatalogsPath(): void
	{
		$service = $this->service('customFields', 'catalogs', 15);
		$this->assertSame('/api/v4/catalogs/15/custom_fields', $service->api_path);
	}

	public function testSubscriptionsRequiresArgs(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('entity_type and entity_id');
		$this->service('subscriptions', 'leads');
	}

	public function testSubscriptionsRejectsUnsupportedEntityType(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('supports only leads or customers');
		$this->service('subscriptions', 'contacts', 1);
	}

	public function testSubscriptionsRejectsInvalidEntityId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('entity_id must be integer/string');
		$this->service('subscriptions', 'leads', 'abc');
	}

	public function testNotesPathWithEntityAndId(): void
	{
		$service = $this->service('notes', 'leads', 55);
		$this->assertSame('/api/v4/leads/55/notes', $service->api_path);
	}

	public function testLinksPathWithEntityAndId(): void
	{
		$service = $this->service('links', 'companies', 9);
		$this->assertSame('/api/v4/companies/9', $service->api_path);
	}
}

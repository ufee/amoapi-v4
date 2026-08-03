<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\CatalogElement;
use Ufee\AmoV4\Services\CatalogElements;
use Ufee\AmoV4\Tests\TestCase;

class CatalogElementsTest extends TestCase
{
	public function testServiceMetaAndPath(): void
	{
		$service = $this->service('catalogElements', 7);

		$this->assertInstanceOf(CatalogElements::class, $service);
		$this->assertSame('/api/v4/catalogs/7/elements', $service->api_path);
		$this->assertSame('elements', $service->entity_key);
		$this->assertSame(CatalogElements::INVOICE_LINK, 'invoice_link');
	}

	public function testRequiresCatalogId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('CatalogElements Service required catalog_id');
		$this->service('catalogElements', 0);
	}

	public function testCustomFieldsArgsAndService(): void
	{
		$service = $this->service('catalogElements', 42);
		$this->assertSame(['catalogs', 42], $service->customFieldsArgs());

		$cfields = $service->customFields();
		$this->assertSame('/api/v4/catalogs/42/custom_fields', $cfields->api_path);
	}

	public function testCreateElementWithCustomFieldsPayload(): void
	{
		$element = $this->service('catalogElements', 3)->create([
			'name' => 'Телефон',
			'custom_fields_values' => [
				(object) [
					'field_id' => 11,
					'field_name' => 'Артикул',
					'field_code' => null,
					'field_type' => 'text',
					'values' => [
						(object) ['value' => 'SKU-1'],
					],
				],
			],
		]);

		$this->assertInstanceOf(CatalogElement::class, $element);
		$element->cf('Артикул')->setValue('34N4124');

		$payload = $element->getChangedRawData();
		$this->assertSame('Телефон', $payload->name);
		$this->assertSame('34N4124', $payload->custom_fields_values[0]->values[0]->value);
	}

	public function testSearchByNameValidation(): void
	{
		$this->expectException(\Ufee\AmoV4\Exceptions\AmoException::class);
		$this->expectExceptionMessage('Invalid search name');
		$this->service('catalogElements', 3)->searchByName('ab');
	}

	public function testApiClientCatalogElementHelper(): void
	{
		$api = $this->makeApiClient();
		// helper ходит в HTTP — проверяем только резолв сервиса через reflection-free path:
		// catalogElements + find is covered in integration; here ensure factory args work
		$service = $api->catalogElements(9);
		$this->assertSame('/api/v4/catalogs/9/elements', $service->api_path);
	}
}

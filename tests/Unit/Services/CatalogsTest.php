<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\Catalog;
use Ufee\AmoV4\Models\CatalogElement;
use Ufee\AmoV4\Services\CatalogElements;
use Ufee\AmoV4\Services\Catalogs;
use Ufee\AmoV4\Services\CustomFields;
use Ufee\AmoV4\Tests\TestCase;

class CatalogsTest extends TestCase
{
	public function testServiceMetaAndTypeConstants(): void
	{
		$service = $this->service('catalogs');

		$this->assertInstanceOf(Catalogs::class, $service);
		$this->assertSame('/api/v4/catalogs', $service->api_path);
		$this->assertSame('catalogs', $service->entity_key);
		$this->assertSame('regular', Catalogs::TYPE_REGULAR);
		$this->assertSame('invoices', Catalogs::TYPE_INVOICES);
		$this->assertSame('products', Catalogs::TYPE_PRODUCTS);
		$this->assertSame('suppliers', Catalogs::TYPE_SUPPLIERS);
	}

	public function testCreateModelRequiresName(): void
	{
		$catalog = $this->service('catalogs')->create([
			'name' => 'Договоры',
			'type' => Catalogs::TYPE_REGULAR,
		]);

		$this->assertInstanceOf(Catalog::class, $catalog);
		$payload = $catalog->getChangedRawData();
		$this->assertSame('Договоры', $payload->name);
		$this->assertSame(Catalogs::TYPE_REGULAR, $payload->type);
	}

	public function testServiceElementsDelegation(): void
	{
		$elements = $this->service('catalogs')->elements(15);
		$this->assertInstanceOf(CatalogElements::class, $elements);
		$this->assertSame('/api/v4/catalogs/15/elements', $elements->api_path);
	}

	public function testModelElementsCreateElementAndCustomFields(): void
	{
		/** @var Catalog $catalog */
		$catalog = $this->service('catalogs')->create(['id' => 12, 'name' => 'List']);

		$elements = $catalog->elements();
		$this->assertInstanceOf(CatalogElements::class, $elements);
		$this->assertSame('/api/v4/catalogs/12/elements', $elements->api_path);

		$element = $catalog->createElement(['name' => 'Item']);
		$this->assertInstanceOf(CatalogElement::class, $element);
		$this->assertSame('Item', $element->name);

		$cfields = $catalog->customFields();
		$this->assertInstanceOf(CustomFields::class, $cfields);
		$this->assertSame('/api/v4/catalogs/12/custom_fields', $cfields->api_path);
	}
}

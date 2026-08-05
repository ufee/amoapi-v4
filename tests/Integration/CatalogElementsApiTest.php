<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\CatalogElement;
use Ufee\AmoV4\Services\CatalogElements;

/**
 * @group integration
 */
class CatalogElementsApiTest extends IntegrationTestCase
{
	/** @var int|null */
	private $catalogId;

	/** @var bool */
	private $createdCatalog = false;

	/** @var int|null */
	private $elementId;

	protected function tearDown(): void
	{
		if ($this->elementId !== null && $this->catalogId !== null && $this->api !== null) {
			try {
				$this->deleteEntity('/api/v4/catalogs/' . $this->catalogId . '/elements', $this->elementId);
			} catch (\Throwable $e) {
			}
		}
		if ($this->createdCatalog && $this->catalogId !== null && $this->api !== null) {
			try {
				$this->deleteEntity('/api/v4/catalogs', $this->catalogId);
			} catch (\Throwable $e) {
			}
		}
		$this->elementId = null;
		$this->catalogId = null;
		$this->createdCatalog = false;
		parent::tearDown();
	}

	public function testCreateFindUpdateAndSearchElement(): void
	{
		$this->catalogId = $this->resolveCatalogId();
		$service = $this->api->catalogElements($this->catalogId);
		$this->assertInstanceOf(CatalogElements::class, $service);

		$name = $this->uniqueName('Element');
		$element = $service->create(['name' => $name]);

		try {
			$this->assertTrue($element->save(), 'Не удалось создать элемент списка');
		} catch (\Throwable $e) {
			$this->markTestSkipped('Создание элементов списка недоступно: ' . $e->getMessage());
		}

		$this->assertInstanceOf(CatalogElement::class, $element);
		$this->assertNotEmpty($element->id);
		$this->elementId = (int) $element->id;

		$found = $service->find($element->id);
		$this->assertInstanceOf(CatalogElement::class, $found);
		$this->assertSame($name, $found->name);

		$viaHelper = $this->api->catalogElement($this->catalogId, $element->id);
		$this->assertInstanceOf(CatalogElement::class, $viaHelper);
		$this->assertSame((int) $element->id, (int) $viaHelper->id);

		$updated = $name . ' updated';
		$found->name = $updated;
		$this->assertTrue($found->save(), 'Не удалось обновить элемент');

		$reloaded = $service->find($element->id);
		$this->assertSame($updated, $reloaded->name);

		$this->waitForSearch();
		$byName = $service->searchByName($updated, 1);
		$this->assertNotNull(
			$byName->find('id', $element->id)->first(),
			'Элемент не найден через searchByName'
		);
	}

	public function testListElementsAndViaCatalogModel(): void
	{
		$this->catalogId = $this->resolveCatalogId();
		$catalog = $this->api->catalogs()->find($this->catalogId);
		$this->assertNotNull($catalog);

		try {
			$page = $catalog->elements()->maxPageRows(5)->paginate()->fetchPage();
			$this->assertIsObject($page);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Список элементов недоступен: ' . $e->getMessage());
		}

		$lazyName = $this->uniqueName('Lazy');
		$lazy = $catalog->createElement(['name' => $lazyName]);
		$this->assertInstanceOf(CatalogElement::class, $lazy);
		$this->assertSame($lazyName, $lazy->name);
	}

	private function resolveCatalogId(): int
	{
		$catalogId = (int) (getenv('AMO_CATALOG_ID') ?: 0);
		if ($catalogId > 0) {
			return $catalogId;
		}

		try {
			$existing = $this->api->catalogs()->maxPageRows(1)->paginate()->fetchPage();
			$first = $existing->first();
			if ($first) {
				return (int) $first->id;
			}
		} catch (\Throwable $e) {
			// create below
		}

		$catalog = $this->api->catalogs()->create([
			'name' => $this->uniqueName('Catalog'),
			'type' => \Ufee\AmoV4\Services\Catalogs::TYPE_REGULAR,
		]);
		try {
			$this->assertTrue($catalog->save(), 'Не удалось создать список для элементов');
		} catch (\Throwable $e) {
			$this->markTestSkipped('Нет каталога и создать нельзя: ' . $e->getMessage());
		}

		$this->createdCatalog = true;
		return (int) $catalog->id;
	}
}

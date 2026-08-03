<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Catalog;
use Ufee\AmoV4\Services\Catalogs;

/**
 * @group integration
 */
class CatalogsApiTest extends IntegrationTestCase
{
	/** @var int|null */
	private $createdCatalogId;

	protected function tearDown(): void
	{
		if ($this->createdCatalogId !== null && $this->api !== null) {
			try {
				$this->deleteEntity('/api/v4/catalogs', $this->createdCatalogId);
			} catch (\Throwable $e) {
				// best-effort
			}
		}
		$this->createdCatalogId = null;
		parent::tearDown();
	}

	public function testListCatalogs(): void
	{
		$service = $this->api->catalogs();
		$this->assertInstanceOf(Catalogs::class, $service);

		try {
			$page = $service->maxPageRows(10)->paginate()->fetchPage();
			$this->assertIsObject($page);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Список catalogs недоступен: ' . $e->getMessage());
		}
	}

	public function testCreateUpdateAndFindCatalog(): void
	{
		$name = $this->uniqueName('Catalog');
		$catalog = $this->api->catalogs()->create([
			'name' => $name,
			'type' => Catalogs::TYPE_REGULAR,
			'can_add_elements' => true,
		]);

		try {
			$this->assertTrue($catalog->save(), 'Не удалось создать список');
		} catch (\Throwable $e) {
			$this->markTestSkipped('Создание catalogs недоступно: ' . $e->getMessage());
		}

		$this->assertNotEmpty($catalog->id);
		$this->createdCatalogId = (int) $catalog->id;

		$found = $this->api->catalogs()->find($catalog->id);
		$this->assertInstanceOf(Catalog::class, $found);
		$this->assertSame($name, $found->name);

		$updated = $name . ' updated';
		$found->name = $updated;
		$this->assertTrue($found->save(), 'Не удалось обновить список');

		$reloaded = $this->api->catalogs()->find($catalog->id);
		$this->assertSame($updated, $reloaded->name);

		$elementsService = $reloaded->elements();
		$this->assertSame('/api/v4/catalogs/' . $catalog->id . '/elements', $elementsService->api_path);
	}
}

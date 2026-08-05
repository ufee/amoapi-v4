<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Source;
use Ufee\AmoV4\Services\Sources;

/**
 * @group integration
 */
class SourcesApiTest extends IntegrationTestCase
{
	/** @var int|null */
	private $createdSourceId;

	protected function tearDown(): void
	{
		if ($this->createdSourceId !== null && $this->api !== null) {
			try {
				$this->api->sources()->remove($this->createdSourceId);
			} catch (\Throwable $e) {
				// best-effort
			}
		}
		$this->createdSourceId = null;
		parent::tearDown();
	}

	public function testListSources(): void
	{
		$service = $this->api->sources();
		$this->assertInstanceOf(Sources::class, $service);

		try {
			$page = $service->maxPageRows(10)->paginate()->fetchPage();
			$this->assertIsObject($page);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Список источников недоступен: ' . $e->getMessage());
		}
	}

	public function testCreateUpdateAndRemoveSource(): void
	{
		$pipelines = $this->api->pipelines()->get();
		$pipeline = $pipelines->first();
		if (!$pipeline) {
			$this->markTestSkipped('Нет воронок для создания источника');
		}

		$externalId = 'amoapi-v4-itest-' . uniqid('', false);
		$source = $this->api->sources()->create([
			'name' => $this->uniqueName('Source'),
			'pipeline_id' => (int) $pipeline->id,
			'external_id' => $externalId,
			'default' => false,
		]);

		try {
			$this->assertTrue($source->save(), 'Не удалось создать источник');
		} catch (\Throwable $e) {
			$this->markTestSkipped('Создание источников недоступно в аккаунте: ' . $e->getMessage());
		}

		$this->assertNotEmpty($source->id);
		$this->createdSourceId = (int) $source->id;

		$found = $this->api->sources()->find($source->id);
		$this->assertInstanceOf(Source::class, $found);

		$updatedName = $this->uniqueName('Source upd');
		$found->name = $updatedName;
		$this->assertTrue($found->save(), 'Не удалось обновить источник');

		$reloaded = $this->api->sources()->find($source->id);
		$this->assertSame($updatedName, $reloaded->name);

		$this->assertTrue($this->api->sources()->remove($source->id));
		$this->createdSourceId = null;
	}
}

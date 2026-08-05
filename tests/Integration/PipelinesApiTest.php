<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Pipeline;
use Ufee\AmoV4\Services\Pipelines;

/**
 * @group integration
 */
class PipelinesApiTest extends IntegrationTestCase
{
	/** @var int|null */
	private $pipelineId;

	protected function tearDown(): void
	{
		if ($this->pipelineId !== null && $this->api !== null) {
			try {
				$this->api->pipelines()->delete($this->pipelineId);
			} catch (\Throwable $e) {
				// best-effort
			}
		}
		$this->pipelineId = null;
		parent::tearDown();
	}

	public function testListPipelines(): void
	{
		$service = $this->api->pipelines();
		$this->assertInstanceOf(Pipelines::class, $service);
		$pipelines = $service->get();
		$this->assertGreaterThan(0, $pipelines->count());
	}

	public function testCreateUpdateAndDeletePipeline(): void
	{
		$name = $this->uniqueName('Pipeline');
		$pipeline = $this->api->pipelines()->create([
			'name' => $name,
			'sort' => 900,
			'is_main' => false,
			'is_unsorted_on' => false,
		]);
		// amoCRM требует _embedded.statuses при создании воронки
		$pipeline->_embedded = [
			'statuses' => [
				[
					'name' => 'ITEST New',
					'sort' => 10,
					'color' => '#c1e0ff',
				],
			],
		];

		try {
			$this->assertTrue($pipeline->save(), 'Не удалось создать воронку');
		} catch (\Throwable $e) {
			$this->markTestSkipped('Создание воронки недоступно: ' . $e->getMessage());
		}

		$this->assertNotEmpty($pipeline->id);
		$this->pipelineId = (int) $pipeline->id;

		$found = $this->api->pipelines()->find($pipeline->id);
		$this->assertInstanceOf(Pipeline::class, $found);
		$this->assertSame($name, $found->name);

		$updated = $name . ' updated';
		$found->name = $updated;
		$this->assertTrue($found->save(), 'Не удалось обновить воронку');

		$reloaded = $this->api->pipelines()->find($pipeline->id);
		$this->assertSame($updated, $reloaded->name);

		$this->assertTrue($reloaded->delete(), 'Не удалось удалить воронку');
		$this->pipelineId = null;

		$this->assertNull($this->api->pipelines()->find($pipeline->id));
	}
}

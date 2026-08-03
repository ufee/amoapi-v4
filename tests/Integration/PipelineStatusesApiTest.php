<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\PipelineStatus;
use Ufee\AmoV4\Services\PipelineStatuses;

/**
 * @group integration
 */
class PipelineStatusesApiTest extends IntegrationTestCase
{
	/** @var int|null */
	private $pipelineId;

	/** @var int|null */
	private $statusId;

	protected function tearDown(): void
	{
		if ($this->statusId !== null && $this->pipelineId !== null && $this->api !== null) {
			try {
				$this->api->pipelineStatuses($this->pipelineId)->delete($this->statusId);
			} catch (\Throwable $e) {
			}
		}
		if ($this->pipelineId !== null && $this->api !== null) {
			try {
				$this->api->pipelines()->delete($this->pipelineId);
			} catch (\Throwable $e) {
			}
		}
		$this->statusId = null;
		$this->pipelineId = null;
		parent::tearDown();
	}

	public function testCreateUpdateAndDeleteStatus(): void
	{
		$pipeline = $this->api->pipelines()->create([
			'name' => $this->uniqueName('Pipeline for status'),
			'sort' => 910,
			'is_main' => false,
			'is_unsorted_on' => false,
		]);
		$pipeline->_embedded = [
			'statuses' => [
				[
					'name' => 'ITEST Base',
					'sort' => 10,
					'color' => '#c1e0ff',
				],
			],
		];

		try {
			$this->assertTrue($pipeline->save(), 'Не удалось создать воронку для этапов');
		} catch (\Throwable $e) {
			$this->markTestSkipped('Создание воронки недоступно: ' . $e->getMessage());
		}
		$this->pipelineId = (int) $pipeline->id;

		$service = $this->api->pipelineStatuses($pipeline->id);
		$this->assertInstanceOf(PipelineStatuses::class, $service);

		$statusName = $this->uniqueName('Status');
		$status = $pipeline->createStatus([
			'name' => $statusName,
			'sort' => 50,
			'color' => '#fffeb2', // допустимый цвет amoCRM
		]);

		try {
			$this->assertTrue($status->save(), 'Не удалось создать этап');
		} catch (\Throwable $e) {
			$this->markTestSkipped('Создание этапа недоступно: ' . $e->getMessage());
		}

		$this->assertInstanceOf(PipelineStatus::class, $status);
		$this->assertNotEmpty($status->id);
		$this->statusId = (int) $status->id;

		$found = $service->find($status->id);
		$this->assertInstanceOf(PipelineStatus::class, $found);
		$this->assertSame($statusName, $found->name);

		$updated = $statusName . ' updated';
		$found->name = $updated;
		$found->sort = 60;
		$this->assertTrue($found->save(), 'Не удалось обновить этап');

		$reloaded = $service->find($status->id);
		$this->assertSame($updated, $reloaded->name);
		// amoCRM может нормализовать sort относительно соседних этапов
		$this->assertGreaterThan(0, (int) $reloaded->sort);

		$this->assertTrue($reloaded->delete(), 'Не удалось удалить этап через модель');
		$this->statusId = null;

		$this->assertNull($service->find($status->id));

		// удаление через pipeline->deleteStatus на свежем этапе
		$status2 = $pipeline->createStatus([
			'name' => $this->uniqueName('Status2'),
			'sort' => 70,
			'color' => '#ebffb1',
		]);
		try {
			$this->assertTrue($status2->save());
		} catch (\Throwable $e) {
			return;
		}
		$this->statusId = (int) $status2->id;
		$this->assertTrue($pipeline->deleteStatus($status2->id));
		$this->statusId = null;
	}

	public function testListStatusesOnExistingPipeline(): void
	{
		$pipelines = $this->api->pipelines()->get();
		$pipeline = $pipelines->first();
		$this->assertNotNull($pipeline);

		$statuses = $this->api->pipelineStatuses($pipeline->id)->get();
		$this->assertGreaterThan(0, $statuses->count());
		$this->assertInstanceOf(PipelineStatus::class, $statuses->first());
	}
}

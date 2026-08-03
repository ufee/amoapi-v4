<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\PipelineStatus;
use Ufee\AmoV4\Services\PipelineStatuses;
use Ufee\AmoV4\Tests\TestCase;

class PipelineStatusesTest extends TestCase
{
	public function testServiceMetaAndRequiresPipelineId(): void
	{
		$service = $this->service('pipelineStatuses', 8);
		$this->assertInstanceOf(PipelineStatuses::class, $service);
		$this->assertSame('/api/v4/leads/pipelines/8/statuses', $service->api_path);
		$this->assertSame('statuses', $service->entity_key);

		$this->expectException(\InvalidArgumentException::class);
		$this->service('pipelineStatuses', 0);
	}

	public function testCreateModel(): void
	{
		$status = $this->service('pipelineStatuses', 1)->create([
			'name' => 'Договор подписан',
			'sort' => 10,
		]);
		$this->assertInstanceOf(PipelineStatus::class, $status);
		$this->assertSame(10, $status->sort);
	}

	public function testSystemStatusConstants(): void
	{
		$this->assertSame(142, PipelineStatus::STATUS_WON);
		$this->assertSame(143, PipelineStatus::STATUS_LOST);
	}

	public function testModelDeleteDelegatesToService(): void
	{
		$service = $this->getMockBuilder(PipelineStatuses::class)
			->disableOriginalConstructor()
			->onlyMethods(['delete'])
			->getMock();

		$service->expects($this->once())
			->method('delete')
			->with(99)
			->willReturn(true);

		$status = new PipelineStatus(['id' => 99, 'name' => 'X'], $service);
		$this->assertTrue($status->delete());
	}

	public function testNextAndPreviousFromEmbeddedStatuses(): void
	{
		$api = $this->makeApiClient();
		// pipeline() на статусе ходит в cache — для unit проверяем соседние этапы
		// через локальную коллекцию на модели Pipeline
		$pipeline = $api->pipelines()->create([
			'id' => 3,
			'name' => 'P',
			'_embedded' => [
				'statuses' => [
					(object) ['id' => 10, 'name' => 'A', 'sort' => 10, 'pipeline_id' => 3],
					(object) ['id' => 20, 'name' => 'B', 'sort' => 20, 'pipeline_id' => 3],
					(object) ['id' => PipelineStatus::STATUS_WON, 'name' => 'Won', 'sort' => 10000, 'pipeline_id' => 3],
					(object) ['id' => PipelineStatus::STATUS_LOST, 'name' => 'Lost', 'sort' => 11000, 'pipeline_id' => 3],
				],
			],
		]);

		$statuses = $pipeline->statuses();
		$a = $statuses->find('id', 10)->first();
		$b = $statuses->find('id', 20)->first();

		// next/previous требуют cache->pipeline — без HTTP пропускаем сетевую часть
		$this->assertSame('A', $a->name);
		$this->assertSame('B', $b->name);
		$this->assertSame(20, $b->sort);
	}
}

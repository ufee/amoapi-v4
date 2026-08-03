<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\Pipeline;
use Ufee\AmoV4\Models\PipelineStatus;
use Ufee\AmoV4\Services\PipelineStatuses;
use Ufee\AmoV4\Services\Pipelines;
use Ufee\AmoV4\Tests\TestCase;

class PipelinesTest extends TestCase
{
	public function testServiceMeta(): void
	{
		$service = $this->service('pipelines');
		$this->assertInstanceOf(Pipelines::class, $service);
		$this->assertSame('/api/v4/leads/pipelines', $service->api_path);
		$this->assertSame('pipelines', $service->entity_key);
	}

	public function testCreateModel(): void
	{
		$pipeline = $this->service('pipelines')->create([
			'name' => 'Рекламации',
			'sort' => 20,
			'is_main' => false,
		]);
		$this->assertInstanceOf(Pipeline::class, $pipeline);
		$this->assertSame('Рекламации', $pipeline->name);
	}

	public function testModelDeleteDelegatesToService(): void
	{
		$service = $this->getMockBuilder(Pipelines::class)
			->disableOriginalConstructor()
			->onlyMethods(['delete'])
			->getMock();

		$service->expects($this->once())
			->method('delete')
			->with(10)
			->willReturn(true);

		$pipeline = new Pipeline(['id' => 10, 'name' => 'X'], $service);
		$this->assertTrue($pipeline->delete());
	}

	public function testCreateStatusAndDeleteStatusHelpers(): void
	{
		$api = $this->makeApiClient();
		$pipeline = $api->pipelines()->create([
			'id' => 55,
			'name' => 'P',
			'_embedded' => [
				'statuses' => [
					(object) ['id' => 1, 'name' => 'New', 'sort' => 10, 'pipeline_id' => 55],
				],
			],
		]);

		$status = $pipeline->createStatus(['name' => 'Договор', 'sort' => 20]);
		$this->assertInstanceOf(PipelineStatus::class, $status);
		$this->assertSame('Договор', $status->name);

		$statusesService = $api->pipelineStatuses(55);
		$this->assertInstanceOf(PipelineStatuses::class, $statusesService);
		$this->assertSame('/api/v4/leads/pipelines/55/statuses', $statusesService->api_path);

		$collection = $pipeline->statuses();
		$this->assertCount(1, $collection);
		$this->assertSame('New', $collection->first()->name);
	}
}

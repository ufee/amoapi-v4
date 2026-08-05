<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Models;

use Ufee\AmoV4\Models\WithCfield;
use Ufee\AmoV4\Tests\Support\EntityFixtures;
use Ufee\AmoV4\Tests\TestCase;

class WithCfieldEntitiesTest extends TestCase
{
	/**
	 * @dataProvider withCfieldProvider
	 */
	public function testDirtyTrackingAndCustomFieldsPayload(string $method, array $args): void
	{
		/** @var WithCfield $model */
		$model = $this->service($method, ...$args)->create([
			'name' => 'Unit',
			'custom_fields_values' => [
				(object) [
					'field_id' => 100,
					'field_name' => 'Email',
					'field_code' => 'EMAIL',
					'field_type' => 'multitext',
					'values' => [
						(object) ['value' => 'old@example.com', 'enum_code' => 'WORK'],
					],
				],
			],
		]);

		$model->name = 'Updated';
		$model->cf('Email')->setValue('new@example.com');

		$this->assertTrue($model->hasChanged('name'));
		$payload = $model->getChangedRawData();
		$this->assertSame('Updated', $payload->name);
		$this->assertSame('new@example.com', $payload->custom_fields_values[0]->values[0]->value);
	}

	/**
	 * @dataProvider withCfieldProvider
	 */
	public function testSaveCreateAndUpdate(string $method, array $args): void
	{
		$real = $this->service($method, ...$args);
		$serviceClass = get_class($real);
		$modelClass = $real->entity_model;

		$service = $this->getMockBuilder($serviceClass)
			->disableOriginalConstructor()
			->onlyMethods(['add', 'update'])
			->getMock();

		$service->expects($this->once())
			->method('add')
			->willReturn((object) ['id' => 11, 'name' => 'Unit', 'updated_at' => 1]);
		$service->expects($this->never())->method('update');

		/** @var WithCfield $model */
		$model = new $modelClass(['name' => 'Unit'], $service);
		$this->assertTrue($model->save());
		$this->assertSame(11, $model->id);

		$serviceUpdate = $this->getMockBuilder($serviceClass)
			->disableOriginalConstructor()
			->onlyMethods(['add', 'update'])
			->getMock();
		$serviceUpdate->expects($this->never())->method('add');
		$serviceUpdate->expects($this->once())
			->method('update')
			->with(22, $this->callback(function ($data) {
				return is_object($data) && ($data->name ?? null) === 'Next';
			}))
			->willReturn((object) ['id' => 22, 'name' => 'Next', 'updated_at' => 2]);

		/** @var WithCfield $existing */
		$existing = new $modelClass(['id' => 22, 'name' => 'Old'], $serviceUpdate);
		$existing->name = 'Next';
		$this->assertTrue($existing->save());
	}

	/**
	 * @dataProvider withCfieldProvider
	 */
	public function testTagsHelpers(string $method, array $args, bool $hasTags): void
	{
		if (!$hasTags) {
			$this->markTestSkipped('Сущность без Tags');
		}

		$model = $this->service($method, ...$args)->create(['name' => 'Unit']);
		$model->attachTag('VIP');
		$model->detachTag('OLD');
		$model->setTags(['A', 'B']);

		$payload = $model->getChangedRawData();
		$this->assertSame([['name' => 'VIP']], $payload->tags_to_add);
		$this->assertSame([['name' => 'OLD']], $payload->tags_to_delete);
		$this->assertSame([['name' => 'A'], ['name' => 'B']], $payload->_embedded['tags']);

		$model->resetTags();
		$payloadReset = $model->getChangedRawData();
		$this->assertNull($payloadReset->_embedded['tags']);
	}

	public function withCfieldProvider(): array
	{
		return EntityFixtures::withCfieldProvider();
	}
}

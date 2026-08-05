<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\Source;
use Ufee\AmoV4\Services\Sources;
use Ufee\AmoV4\Tests\TestCase;

class SourcesTest extends TestCase
{
	public function testServiceMeta(): void
	{
		$service = $this->service('sources');
		$this->assertInstanceOf(Sources::class, $service);
		$this->assertSame('/api/v4/sources', $service->api_path);
		$this->assertSame('sources', $service->entity_key);
	}

	public function testCreateModelRequiresNameInPayload(): void
	{
		$source = $this->service('sources')->create(['name' => 'Sales desk']);
		$this->assertInstanceOf(Source::class, $source);

		$payload = $source->getChangedRawData();
		$this->assertSame('Sales desk', $payload->name);
	}

	public function testModelDeleteDelegatesToRemove(): void
	{
		$service = $this->getMockBuilder(Sources::class)
			->disableOriginalConstructor()
			->onlyMethods(['remove'])
			->getMock();

		$service->expects($this->once())
			->method('remove')
			->with(15)
			->willReturn(true);

		$source = new Source(['id' => 15, 'name' => 'X'], $service);
		$this->assertTrue($source->delete());
	}

	public function testRemoveRejectsEmptyArray(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Source IDs must be integer/string or non empty array');
		$this->service('sources')->remove([]);
	}

	public function testRemoveRejectsInvalidIdInBatch(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Each source ID must be integer/string');
		$this->service('sources')->remove([1, 'x']);
	}
}

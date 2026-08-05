<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Tests\TestCase;

class FilesServiceTest extends TestCase
{
	public function testAddIsDisabled(): void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('Use files()->upload()');
		$this->service('files')->add(['name' => 'x']);
	}

	public function testDefaultPageLimit(): void
	{
		$this->assertSame(50, $this->service('files')->getQueryArg('limit'));
	}

	public function testAttachRejectsInvalidEntityType(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Entity type must be one of');
		$this->service('files')->attachToEntity('pipelines', 1, 'uuid-1');
	}

	public function testDetachRejectsInvalidEntityType(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Entity type must be one of');
		$this->service('files')->detachFromEntity('notes', 1, ['uuid-1']);
	}

	public function testNormalizeUuidPayloadViaReflection(): void
	{
		$files = $this->service('files');
		$ref = new \ReflectionMethod($files, 'normalizeUuidPayload');
		$ref->setAccessible(true);

		$this->assertSame([['uuid' => 'a']], $ref->invoke($files, 'a'));
		$this->assertSame(
			[['uuid' => 'a'], ['uuid' => 'b']],
			$ref->invoke($files, ['a', (object) ['uuid' => 'b']])
		);
	}

	public function testNormalizeUuidPayloadRejectsEmpty(): void
	{
		$files = $this->service('files');
		$ref = new \ReflectionMethod($files, 'normalizeUuidPayload');
		$ref->setAccessible(true);

		$this->expectException(\InvalidArgumentException::class);
		$ref->invoke($files, []);
	}

	public function testNormalizeFileUuidPayload(): void
	{
		$files = $this->service('files');
		$ref = new \ReflectionMethod($files, 'normalizeFileUuidPayload');
		$ref->setAccessible(true);

		$this->assertSame([['file_uuid' => 'u1']], $ref->invoke($files, 'u1'));
		$this->assertSame(
			[['file_uuid' => 'u1'], ['file_uuid' => 'u2']],
			$ref->invoke($files, ['u1', (object) ['file_uuid' => 'u2']])
		);
	}

	public function testCreateSessionRequiresParams(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('file_name and file_size');
		$this->service('files')->createSession(['file_name' => 'a.txt']);
	}

	public function testUploadRequiresFileNameForBinary(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('file_name and file_size');
		$this->service('files')->upload('binary-content');
	}

	public function testUploadRejectsInvalidSource(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('file path, binary string or resource');
		$this->service('files')->upload(123);
	}

	public function testFindRejectsEmptyUuid(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('File UUID must be non-empty string');
		$this->service('files')->find('');
	}

	public function testGetDriveHostFromParam(): void
	{
		$api = $this->makeApiClient();
		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');
		$this->assertSame('drive-b.amocrm.ru', $api->files()->getDriveHost());
	}
}

